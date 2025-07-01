<?php

namespace App\Traits\Validates;

use App\Http\Resources\ReservationResource;
use App\Models\Agenda;
use App\Models\Horary;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait ValidatesReservationTrait
{

    public function validateExistsAgendaAndReservations($id, $params, $days = null)
    {
        try {
            $doctorId  = $params['doctor_id'] ?? null;
            $agenda = Agenda::activeForID($id)->first();
            if (!isset($agenda->id)) {
                return $this->errorResponse('Agenda no encontrada', 404);
            }
            #Verificaciones de reservas con respecto a los horarios de la agenda a modificar/eliminar
            $reservation = Reservation::where('iddoctor', $doctorId)
                ->active();
            if (!is_null($days)) {
                $reservation = $reservation->whereIn(DB::raw('WEEKDAY(date) + 1'), $days); #DAYOFWEEK solo funciona en MySQL
            }

            $reservation = $reservation->whereDate('date', '>=', date('Y-m-d'))
                ->whereHas('horary', function ($q) use ($id) {
                    $q->where('idagenda', $id);
                })->get()->load('patient.rrhh', 'doctor.rrhh', 'horary');
            $detail = [];

            if (count($reservation) > 0) {
                if (isset($params['preview']) && $params['preview']) {

                    $detail['for_reschedule'] = ReservationResource::collection($reservation);

                    return $this->successResponse('Se encontraron detalles a validar.', $detail);
                }
                #Reagendar todas las reservas con conflicto
                $arrayIdsReservation = collect($reservation)->map(function ($object) {
                    return $object->id;
                })->toArray();
                dd('Reservas por Reagendar' . $arrayIdsReservation);
                # Actualizar cada reserva de manera indivual para que se mande su correo de reagendación
                // $validate = $this->rescheduleRerservationArray($arrayIdsReservation);
                // if (!$validate->original['status'])  return $validate;


                return $this->successResponse('Sus reservas han sido marcadas como por reagendar');
            }

            $detail['for_reschedule'] = ReservationResource::collection([]); //no existe reservas por reagendar asi no rompe el flujo

            return $this->successResponse('OK', $detail);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function validateParamsCreate($params)
    {
        try {
            $date       = $params['date'];
            $idhorary   = $params['idhorary'];
            $patientId    = $params['idpatient'];
            $validateRole = User::activeForID($patientId)->where('idrole', 2)->first();

            if ($validateRole) {
                return $this->errorResponse('los doctores no pueden solicitar una reserva', 403);
            }
            if ($validateRole)

                if (!in_array(Auth::user()->idrole, [2, 3])) {
                    return $this->errorResponse('Solo los pacientes y doctores pueden crear reservas', 403);
                }

            //VALIDACION: HORARIO SELECCIONADA EXISTA
            $horary = Horary::where('id', $idhorary)->active()->first();
            if (!$horary) {
                return $this->errorResponse('Horario no encontrado', 404);
            }

            //VALIDACION: FECHA NO SEA MENOR A HOY
            if ($date < date('Y-m-d', strtotime('-15 days'))) {
                return $this->errorResponse('Fecha no aceptable', 422);
            }

            $day = Carbon::parse($date)->isoWeekday();
            //VALIDACION: DIA DE LA SEMANA DE LA FECHA INGRESADA CORRESPONDA AL DIA DEL HORARY
            $horary = Horary::where('id', $idhorary)->where('idday', $day)
                ->active()->first();
            if (!$horary) {
                return $this->errorResponse('Horario no admitido', 404);
            }

            //VALIDACION: HORARIO SELECCIONADA ESTE LIBRE
            $reservation = Reservation::where('date', $date)->where('idhorary', $idhorary)
                ->active()->first();

            if (!is_null($reservation)) {
                return $this->errorResponse('Horario ocupado', 422);
            }
            return $this->successResponse('Ok');
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
