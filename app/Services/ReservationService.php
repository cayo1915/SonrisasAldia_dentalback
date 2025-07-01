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
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    use HasResponse;
    use ValidatesReservationTrait;

    /** @var ReminderService */
    private $reminderService;

    public function __construct(ReminderService $reminderService)
    {
        $this->reminderService = $reminderService;
    }

    public function getReservations($withPagination, $request)
    {
        try {
            $reservations = Reservation::filters($request)->active();
            $reservations = $reservations->orderBy('id', 'desc');
            $reservations = !empty($withPagination)
                ? $reservations->paginate($withPagination['perPage'], page: $withPagination['page'])
                : $reservations->get();
            $paginationTotal = null;
            if (!empty($withPagination)) {
                $paginationTotal = $reservations->total();
            }
            $reservations = ReservationResource::collection($reservations->load('patient.rrhh', 'doctor.rrhh', 'horary'));
            return $this->successPaginationResponse('Lista exitosa', $paginationTotal, $reservations);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getReservationById($idreservation)
    {
        try {
            $reservation = Reservation::activeForID('id', $idreservation)
                ->get()->load('patient.rrhh', 'doctor.rrhh', 'horary');

            return $this->successResponse("Lectura exitosa", ReservationResource::collection($reservation));
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function patientsAttended($params)
    {
        $doctorId = $params['doctor_id'] ?? Auth::id();
        $patients = User::whereHas('reservations', function ($query) use ($doctorId) {
            $query->where('iddoctor', $doctorId)
                ->filters();
        })->with([
            'reservations' => function ($query) use ($doctorId) {
                $query->where('iddoctor', $doctorId)
                    ->filters();
            },
            'clinicalHistories'
        ])->get();

        $patients = PatientResource::collection($patients->load('rrhh'));
        return $this->successResponse("Lectura exitosa", $patients);
    }

    /**
     * @throws \Throwable
     */
    public function createReservation($request): JsonResponse
    {
        DB::beginTransaction();
        try {
            # Valida los parámetros
            $validate = $this->validateParamsCreate($request);
            if (!$validate->original['status']) return $validate;

            #Guarda parámetros en variables
            $date     = $request['date'];
            $idhorary = $request['idhorary'];
            $patientId = $request['idpatient'];

            $horary = Horary::where('id', $idhorary)->active()->first();

            if (strtotime($request['date']) < strtotime('-15 days')) {
                return $this->errorResponse('No puede crear una reserva para una fecha anterior a 15 días atrás', 422);
            }

            $agenda = Agenda::activeForID($horary->idagenda)->first();
            if (!$agenda) {
                return $this->errorResponse('No se puede generar reservas. La agenda no está activa.', 404);
            }

            # Crea el registro de la reserva
            $reservation = Reservation::create([
                'date'              => $date,
                'total'             => $price ?? 0,
                'idpatient'         => $patientId,
                'iddoctor'          => $agenda->iddoctor,
                'idhorary'          => $idhorary,
                'type_modality'     => $request['type_modality'] ?? null,
                'is_confirmed'      => $request['is_confirmed'] ?? false,
                'is_paid'           => $request['is_paid'] ?? false,
                'is_attended'       => $request['is_attended'] ?? false,
                'total'             => $request['total'] ?? 0,
            ]);

            $response = $this->reminderService->generateReservationReminders($reservation->id);
            if (!$response->original['status']) {
                return $response;
            }
            event(new ReservationCreatedEvent($reservation));

            DB::commit();
            return $this->successResponse("Reserva creada con éxito", $reservation, 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function canReschedule($id)
    {
        $oldReservation = Reservation::activeForID($id)
            ->where('is_attended', false)
            ->where('is_rescheduled', false)
            ->first();

        if (!$oldReservation) {
            return $this->errorResponse('La reserva ya no puede ser reprogramada.', 422);
        }

        // Validar que aún se puede reprogramar (mínimo 2 horas antes)
        if ($oldReservation->date < now()->addHours(2)) {
            return $this->errorResponse('La reserva ya no puede ser reprogramada.', 422);
        }

        return $this->successResponse('La reserva puede ser reprogramada.');
    }

    public function rescheduleReservation($id, $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $canReschedule = $this->canReschedule($id);
            if (isset($canReschedule->original['status']) && !$canReschedule->original['status']) {
                return $this->errorResponse(
                    $canReschedule->original['data']['message'],
                    $canReschedule->original['code']
                );
            }

            $oldReservation = Reservation::activeForID($id)->where('is_attended', false)->first();

            // Cancelar o actualizar estado de la reserva anterior
            $oldReservation->update([
                'is_rescheduled' => true,
                'rescheduled_at' => now(),
                'status'         => Status::INACTIVE->value,
            ]);

            // Preparar parámetros para nueva reserva
            $request['idpatient']       = $oldReservation->idpatient;
            $request['is_confirmed']    = $oldReservation->is_confirmed;
            $request['is_paid']         = $oldReservation->is_paid;
            $request['is_attended']     = $oldReservation->is_attended;
            $request['total']            = $oldReservation->total;

            // Crear nueva reserva
            $newReservation = $this->createReservation($request);

            DB::commit();
            return $this->successResponse('Reserva reprogramada con éxito', $newReservation->original['data']['detail']);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function updateAtrributes($id, $request)
    {
        DB::beginTransaction();
        try {
            // Validar parámetros
            $validation = Validator::make($request, [
                'is_confirmed'      => ['nullable', 'boolean'],
                'is_paid'           => ['nullable', 'boolean'],
                'is_attended'       => ['nullable', 'boolean'],
                'is_rescheduled'    => ['nullable', 'boolean'],
                'status'            => [
                    'sometimes',
                    'string',
                    Rule::in([Status::ACTIVE->value, Status::DELETED->value,])
                ],
                'total'             => ['nullable', 'integer', 'min:0']
            ]);

            if ($validation->fails()) {
                return $this->errorResponse($validation->errors(), 422);
            }

            $reservation = Reservation::where('id', $id)
                ->where('is_rescheduled', false)
                ->active()
                ->first();

            if (!$reservation) {
                return $this->errorResponse('Reserva no válida', 404);
            }

            $reservation->update($request);

            if (isset($request['total']) && !empty($request['total'])) {
                SaleOrder::where('order_id', $id)
                    ->where('order_type', Reservation::class)
                    ->where('payment_status', 'Pendiente')
                    ->update(['total_amount' => $request['total']]);
            }

            $reservation->load('saleOrders');

            DB::commit();
            return $this->successResponse('Actualizado con éxito.', $reservation);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->errorResponse('Error al actualizar la reserva.', $th->getMessage(), 500);
        }
    }
}
