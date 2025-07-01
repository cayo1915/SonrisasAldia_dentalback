<?php

namespace App\Services;

use App\Enums\NotificationDeliveryStatus;
use App\Http\Resources\NotificationsResource;
use App\Models\DetailUserNotifications;
use App\Models\Notifications;
use App\Models\User;
use App\Traits\HasResponse;
use App\Traits\NotificationHelpers\SendNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

class NotificationsService
{
    use HasResponse;
    use SendNotification;

    public function get($request, $withPagination)
    {
        try {
            $user = JWTAuth::user();
            if (!$user) {
                return $this->errorResponse('Usuario no autenticado', 401);
            }

            $notificationsQuery = DetailUserNotifications::whereHas('notification', function ($query) {
                $query->active()->filters();
            })->where('idreceiver', $user->id)
                ->filters()
                ->orderBy('id', 'desc');

            if (!empty($withPagination)) {
                $notificationsPaginated = $notificationsQuery->paginate($withPagination['perPage'], page: $withPagination['page']);
                $paginationTotal = $notificationsPaginated->total();
                $notifications = $notificationsPaginated->load('notification.sender.rrhh');
            } else {
                $notifications = $notificationsQuery->get()->load('notification.sender.rrhh');
                $paginationTotal = $notifications->count();
            }

            $notificationsResource = NotificationsResource::collection($notifications);

            return $this->successPaginationResponse('Lista exitosa', $paginationTotal, $notificationsResource);
        } catch (\Throwable $th) {
            Log::error('Error al obtener notificaciones: ' . $th->getMessage());
            return $this->errorResponse('Error interno al obtener notificaciones', 500);
        }
    }


    public function create(array $params)
    {
        DB::beginTransaction();

        try {
            if (empty($params['ids_receiver']) || !is_array($params['ids_receiver'])) {
                return $this->errorResponse("No existe ningún destinatario", 400);
            }

            if (empty($params['message_title'])) {
                return $this->errorResponse("El título del mensaje es requerido", 400);
            }

            $activeUsersCount = User::whereIn('id', $params['ids_receiver'])->active()->count();
            if ($activeUsersCount !== count($params['ids_receiver'])) {
                return $this->errorResponse("Uno o más destinatarios no existen o están inactivos", 404);
            }

            $idsender = $params['idsender'] ?? (auth()->check() ? auth()->id() : null);
            if (!$idsender) {
                return $this->errorResponse("No se puede determinar el remitente", 401);
            }

            $send = $this->sendNotification($params);

            DB::commit();
            return $this->successResponse('Notificación creada con éxito', $send, 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error al crear notificación: " . $th->getMessage());
            throw $th;
        }
    }

    public function markNotificationAsViewed($id)
    {
        DB::beginTransaction();
        try {
            $user = JWTAuth::user();
            if (!$user) {
                return $this->errorResponse('Usuario no autenticado', 401);
            }

            $notification = DetailUserNotifications::whereHas('notification', function ($query) use ($id) {
                $query->activeForID($id)->active();
            })->where('idreceiver', $user->id)
                ->notificationSend()
                ->filters()
                ->first();

            if (!$notification) {
                return $this->errorResponse('Usted no es el receptor o la notificación dejó de estar pendiente', 403);
            }

            $notification->markAsViewed();
            $notification = $notification->fresh();

            DB::commit();

            $notificationResource = NotificationsResource::make($notification->load('notification.sender.rrhh'));

            return $this->successResponse('Registro modificado a visto', $notificationResource);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Error en changeStatusN: ' . $th->getMessage());
            return $this->errorResponse('Error interno al cambiar estado de notificación', 500);
        }
    }


    public function delete($id)
    {
        DB::beginTransaction();
        try {
            #Validar existencia del registro
            $notification = Notifications::activeForId($id)->first();
            if (!$notification) return $this->errorResponse('El registro no existe', 404);
            $notification->markAsDeleted();
            DB::commit();
            return $this->successResponse('Notificacion eliminada', $notification);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}
