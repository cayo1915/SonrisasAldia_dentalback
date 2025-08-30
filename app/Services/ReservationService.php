<?php

namespace App\Services;

use App\Enums\Status;
use Illuminate\Validation\Rule;
use App\Events\ReservationCreatedEvent;
use App\Http\Resources\PatientResource;
use App\Http\Resources\ReservationResource;
use App\Models\Agenda;
use App\Models\Horary;
use App\Models\Reservation;
use App\Models\SaleOrder;
use App\Models\User;
use App\Traits\HasResponse;
use App\Traits\Validates\ValidatesReservationTrait;
use App\Traits\NotificationHelpers\SendNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    use HasResponse;
    use ValidatesReservationTrait;
    use SendNotification;

    /** @var ReminderService */
    private $reminderService;

    public function __construct(ReminderService $reminderService)
    {
        $this->reminderService = $reminderService;
    }

    /**
     * @throws \Throwable
     */
    public function createReservation($request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'idpatient' => 'required|exists:users,id',
                'iddoctor' => 'required|exists:users,id',
                'idhorary' => 'required|exists:horaries,id',
                'date' => 'required|date',
            ]);

            if ($validator->fails()) {
                DB::rollBack();
                return $this->errorResponse('Datos de validación incorrectos', $validator->errors(), 422);
            }

            // Crear la reserva
            $reservation = Reservation::create([
                'idpatient' => $request->idpatient,
                'iddoctor' => $request->iddoctor,
                'idhorary' => $request->idhorary,
                'date' => $request->date,
                'status' => Status::ACTIVE->value,
                'is_confirmed' => false,
            ]);

            DB::commit();

            $patient = User::find($request->idpatient);
            $doctor = User::find($request->iddoctor);
            $horary = Horary::find($request->idhorary);

            if ($patient && $doctor && $horary) {
                $usersToNotify = collect([$doctor->id]);
                $receptionist = User::where('idrole', 3)->first();
                if ($receptionist) {
                    $usersToNotify->push($receptionist->id);
                }

                $this->sendNotification([
                    'ids_receiver' => $usersToNotify->toArray(),
                    'message_title' => 'Cita agendada',
                    'message_body' => 'Un paciente ha agendado una nueva cita',
                    'data_json' => [
                        'type' => 'scheduled',
                        'patientName' => $patient->rrhh->name ?? $patient->name,
                        'date' => Carbon::parse($request->date)->format('d M Y'),
                        'time' => Carbon::parse($horary->start_time)->format('h:i A')
                    ]
                ]);
            }

            return $this->successResponse("Reserva creada con éxito", new ReservationResource($reservation), 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function rescheduleReservation($id, $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'idhorary' => 'required|exists:horaries,id',
                'date' => 'required|date',
            ]);

            if ($validator->fails()) {
                DB::rollBack();
                return $this->errorResponse('Datos de validación incorrectos', $validator->errors(), 422);
            }

            $oldReservation = Reservation::find($id);
            if (!$oldReservation) {
                DB::rollBack();
                return $this->errorResponse('Reserva no encontrada', [], 404);
            }

            // Actualizar la reserva
            $oldReservation->update([
                'idhorary' => $request->idhorary,
                'date' => $request->date,
            ]);

            $patient = User::find($oldReservation->idpatient);
            $doctor = User::find($oldReservation->iddoctor);
            $horary = Horary::find($request->idhorary);

            if ($patient && $doctor && $horary) {
                $usersToNotify = collect([$doctor->id]);
                $receptionist = User::where('idrole', 3)->first();
                if ($receptionist) {
                    $usersToNotify->push($receptionist->id);
                }

                $this->sendNotification([
                    'ids_receiver' => $usersToNotify->toArray(),
                    'message_title' => 'Cita reprogramada',
                    'message_body' => 'Se ha reprogramado una cita',
                    'data_json' => [
                        'type' => 'rescheduled',
                        'patientName' => $patient->rrhh->name ?? $patient->name,
                        'date' => Carbon::parse($request->date)->format('d M Y'),
                        'time' => Carbon::parse($horary->start_time)->format('h:i A')
                    ]
                ]);
            }

            $newReservation = Reservation::find($id);
            DB::commit();
            return $this->successResponse('Reserva reprogramada con éxito', new ReservationResource($newReservation));
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function updateAtrributes($id, $request)
    {
        DB::beginTransaction();
        try {
            $reservation = Reservation::find($id);
            if (!$reservation) {
                DB::rollBack();
                return $this->errorResponse('Reserva no encontrada', [], 404);
            }

            $reservation->update($request);

            $patient = User::find($reservation->idpatient);
            $doctor = User::find($reservation->iddoctor);
            $horary = Horary::find($reservation->idhorary);

            if ($patient && $doctor && $horary) {
                $usersToNotify = collect([$doctor->id]);
                $receptionist = User::where('idrole', 3)->first();
                if ($receptionist) {
                    $usersToNotify->push($receptionist->id);
                }

                // Notificación de Cita Confirmada
                if (isset($request['is_confirmed']) && $request['is_confirmed'] && !$reservation->is_confirmed) {
                    $this->sendNotification([
                        'ids_receiver' => $usersToNotify->toArray(),
                        'message_title' => 'Cita confirmada',
                        'message_body' => 'Un paciente ha confirmado su cita',
                        'data_json' => [
                            'type' => 'confirmed',
                            'patientName' => $patient->rrhh->name ?? $patient->name,
                            'date' => Carbon::parse($reservation->date)->format('d M Y'),
                            'time' => Carbon::parse($horary->start_time)->format('h:i A')
                        ]
                    ]);
                }

                // Notificación de Cita Cancelada
                if (isset($request['status']) && $request['status'] === Status::DELETED->value && $reservation->status !== Status::DELETED->value) {
                    $this->sendNotification([
                        'ids_receiver' => $usersToNotify->toArray(),
                        'message_title' => 'Cita cancelada',
                        'message_body' => 'Un paciente ha cancelado su cita',
                        'data_json' => [
                            'type' => 'cancelled',
                            'patientName' => $patient->rrhh->name ?? $patient->name,
                            'date' => Carbon::parse($reservation->date)->format('d M Y'),
                            'time' => Carbon::parse($horary->start_time)->format('h:i A')
                        ]
                    ]);
                }
            }

            DB::commit();
            return $this->successResponse('Actualizado con éxito.', new ReservationResource($reservation));
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->errorResponse('Error al actualizar la reserva.', $th->getMessage(), 500);
        }
    }

    public function getReservations($request): JsonResponse
    {
        try {
            $reservations = Reservation::with(['patient', 'doctor', 'horary'])
                ->where('status', '!=', Status::DELETED->value)
                ->get();

            return $this->successResponse('Reservas obtenidas con éxito', ReservationResource::collection($reservations));
        } catch (\Throwable $th) {
            return $this->errorResponse('Error al obtener las reservas', $th->getMessage(), 500);
        }
    }

    public function getReservationById($id): JsonResponse
    {
        try {
            $reservation = Reservation::with(['patient', 'doctor', 'horary'])->find($id);
            
            if (!$reservation) {
                return $this->errorResponse('Reserva no encontrada', [], 404);
            }

            return $this->successResponse('Reserva obtenida con éxito', new ReservationResource($reservation));
        } catch (\Throwable $th) {
            return $this->errorResponse('Error al obtener la reserva', $th->getMessage(), 500);
        }
    }

    public function patientsAttended($request): JsonResponse
    {
        try {
            $attendedReservations = Reservation::with(['patient', 'doctor'])
                ->where('status', Status::COMPLETED->value)
                ->get();

            return $this->successResponse('Pacientes atendidos obtenidos con éxito', ReservationResource::collection($attendedReservations));
        } catch (\Throwable $th) {
            return $this->errorResponse('Error al obtener pacientes atendidos', $th->getMessage(), 500);
        }
    }
}
