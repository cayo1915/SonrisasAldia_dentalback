<?php

namespace App\Traits\NotificationHelpers;

use App\Enums\NotificationDeliveryStatus;
use App\Models\DetailUserNotifications;
use App\Models\Notifications;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

trait SendNotification
{

    public function sendNotification(array $params)
    {
        if (empty($params['ids_receiver']) || !is_array($params['ids_receiver'])) {
            Log::error("Error en notificación: ids_receiver vacío o no es array", $params);
            throw ValidationException::withMessages(['ids_receiver' => 'No existe ningún destinatario']);
        }

        if (empty($params['message_title'])) {
            Log::error("Error en notificación: message_title vacío", $params);
            throw ValidationException::withMessages(['message_title' => 'El título del mensaje es requerido']);
        }

        $activeUsersCount = User::whereIn('id', $params['ids_receiver'])->active()->count();
        if ($activeUsersCount !== count($params['ids_receiver'])) {
            Log::error("Error en notificación: uno o más destinatarios no existen o están inactivos", $params);
            throw ValidationException::withMessages(['ids_receiver' => 'Uno o más destinatarios no existen o están inactivos']);
        }

        $idsender = $params['idsender'] ?? (auth()->check() ? auth()->id() : null);
        if (!$idsender) {
            Log::error("Error en notificación: no se pudo determinar el remitente", $params);
            throw ValidationException::withMessages(['idsender' => 'No se puede determinar el remitente']);
        }

        $dataJson = (object) ($params['data_json'] ?? []);

        $notification = Notifications::create([
            'idsender'      => $idsender,
            'message_title' => $params['message_title'],
            'message_body'  => $params['message_body'] ?? null,
            'data_json'     => json_encode($dataJson),
            'date_sent'     => Carbon::now()->toDateTimeString(),
        ]);

        foreach ($params['ids_receiver'] as $idReceiver) {
            DetailUserNotifications::firstOrCreate([
                'idreceiver'     => $idReceiver,
                'idnotification' => $notification->id,
            ], [
                'delivery_status' => NotificationDeliveryStatus::SENT->value,
            ]);

            $user = User::activeForID($idReceiver)->activeNotification()->first();

            if ($user) {
                $count = DetailUserNotifications::whereHas('notification', fn($q) => $q->active())
                    ->where('idreceiver', $idReceiver)
                    ->notificationSend()
                    ->active()
                    ->count();

                $payload = [
                    "url" => $dataJson->url ?? null,
                    "count" => $count,
                    "idnotification" => $notification->id,
                ];

                if (isset($dataJson->idurl)) {
                    $payload['idurl'] = $dataJson->idurl;
                }

                // Enviar push si tiene token
                if (!empty($user->token_epn)) {
                    $status = $this->sendNotificationMobile(
                        $user->token_epn,
                        $params['message_title'],
                        $params['message_body'] ?? '',
                        $payload
                    );

                    if ($status !== 200) {
                        Log::warning("Fallo envío push a usuario ID {$user->id} (status: $status)");

                        DetailUserNotifications::where([
                            'idreceiver' => $idReceiver,
                            'idnotification' => $notification->id,
                        ])->update([
                            'delivery_status' => NotificationDeliveryStatus::FAILED->value,
                        ]);
                    }
                }
            }
        }

        Log::info("Notificación {$notification->id} enviada por usuario {$idsender}");
        return $notification;
    }

    public function sendNotificationMobile($token, $title, $bodyText, $data = [])
    {
        $statusCode = 0;

        if (!is_null($token)) {
            try {
                $client = new Client();

                $response = $client->post('https://exp.host/--/api/v2/push/send', [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'json' => [
                        'to' => $token,
                        'title' => $title,
                        'body' => $bodyText,
                        'data' => $data,
                    ],
                ]);

                $statusCode = $response->getStatusCode();

                // Puedes registrar o manejar la respuesta si quieres
                $responseData = json_decode($response->getBody()->getContents(), true);

                if (isset($responseData['errors'])) {
                    Log::error('Expo Notification Error:', $responseData['errors']);
                }
            } catch (RequestException $e) {
                $statusCode = $e->getCode();

                Log::error('Expo Notification Request Exception', [
                    'message' => $e->getMessage(),
                    'request' => optional($e->getRequest())->getBody(),
                    'response' => optional($e->getResponse())->getBody()?->getContents(),
                ]);
            } catch (\Throwable $e) {
                $statusCode = 500;

                Log::error('Expo Notification Error', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return $statusCode;
    }
}
