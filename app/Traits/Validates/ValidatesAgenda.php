<?php

namespace App\Traits\Validates;

use App\Http\Resources\AgendaResource;
use App\Http\Resources\ReservationResource;
use App\Models\Agenda;
use App\Models\AgendaDaysHours;
use App\Models\Day;
use App\Models\Horary;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

trait ValidatesAgenda
{
    use ValidatesDoctor;

    protected function validateAgenda($idagenda, $params)
    {
        try {
            // Si es solo vista previa, devolver éxito sin buscar agenda
            if (!empty($params['preview'])) {
                return $this->successResponse('Agenda del doctor encontrada (preview)', []);
            }
            $doctorId = $params['doctor_id'] ?? null;

            // Validar doctor
            $validate = $this->validateDoctor($doctorId);
            if (!$validate->original['status']) {
                return $validate;
            }

            $doctorId = $validate->original['data']['detail']->id;

            // Buscar agenda del doctor
            $agenda = Agenda::where('id', $idagenda)
                ->where('iddoctor', $doctorId)
                ->availability()
                ->first();

            if (!$agenda) {
                return $this->errorResponse('Agenda del doctor no encontrada', 404);
            }

            // Verificar si hay horarios asociados
            $horaryIds = Horary::where('idagenda', $idagenda)
                ->active()
                ->pluck('id');

            if ($horaryIds->isNotEmpty()) {
                return $this->errorResponse('Esta agenda tiene horarios creados', 404, $horaryIds);
            }

            return $this->successResponse('Agenda del doctor encontrada', $agenda);
        } catch (\Throwable $th) {
            // Puedes lanzar la excepción si deseas que se capture en un handler global
            throw $th;
        }
    }

    protected function shouldRecreateAgendaSchedules(array $params, $agenda): bool
    {
        $duration = (int) ($params['duration_hour'] ?? 0);
        $waitTime = (int) ($params['wait_time_hour'] ?? 0);
        $breakStatus = filter_var($params['break_status'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // Colaciones ingresadas en el formulario (si están activas)
        $breakStart = $breakStatus && !empty($params['break_start'])
            ? Carbon::parse($params['break_start'])
            : null;

        $breakEnd = $breakStatus && !empty($params['break_end'])
            ? Carbon::parse($params['break_end'])
            : null;

        // Valores actuales en la agenda
        $currentStart = $agenda->break_start ? Carbon::parse($agenda->break_start) : null;
        $currentEnd = $agenda->break_end ? Carbon::parse($agenda->break_end) : null;

        // Comparación
        $durationChanged = (int) $agenda->duration_hour !== $duration;
        $waitTimeChanged = (int) $agenda->wait_time_hour !== $waitTime;

        $breakDisabledNow = !$breakStatus && ($currentStart !== null || $currentEnd !== null);

        $breakChanged = $breakStatus && (
            $currentStart != $breakStart || $currentEnd != $breakEnd
        );

        return $durationChanged || $waitTimeChanged || $breakDisabledNow || $breakChanged;
    }

    public function verificationDateAvailability($params)
    {
        try {
            $doctorId = $params['doctor_id'] ?? null;
            #Obtener todos los registro de agenda de eventualidad que contienen a la agenda a crear, caso contrario lo dejo pasar
            $eventuality = Agenda::where('iddoctor', $doctorId)
                ->active()
                ->unAvailability()
                ->get();

            $minimun_time_schedule = 1; // Se evalúa si hay al menos 1 hora de separación entre horas de inicio/fin

            if (count($eventuality) > 0) {
                foreach ($eventuality as $blockAgenda) {
                    // Obtener los días y horas de no disponibilidad de la agenda de bloqueo (AgendaDaysHours)
                    $blockedDaysHours = AgendaDaysHours::where('idagenda', $blockAgenda->id)
                        ->active()
                        ->get(['idday', 'start_hour', 'end_hour']);

                    // Convertir los días y horas bloqueadas a un formato más fácil de manejar (por ejemplo, en arrays)
                    $blockedDays = [];
                    foreach ($blockedDaysHours as $blockedDay) {
                        $blockedDays[$blockedDay->idday][] = [
                            'start' => $blockedDay->start_hour,
                            'end'   => $blockedDay->end_hour
                        ];
                    }

                    // Verificar cruce de días y horas con la nueva agenda a crear
                    foreach ($params['days_array'] as $newDay) {
                        $dayId = $newDay['day'];

                        if (isset($blockedDays[$dayId])) {
                            $newStart = Carbon::parse(date('Y-m-d') . ' ' . $newDay['start']);
                            $newEnd = Carbon::parse(date('Y-m-d') . ' ' . $newDay['end']);

                            foreach ($blockedDays[$dayId] as $blockedHour) {
                                // Verificar si hay cruce de horarios (minimun_time_schedule)
                                $initial = Carbon::parse(date('Y-m-d') . ' ' . $newDay['start'])->timestamp - (Carbon::parse(date('Y-m-d') . ' ' . $blockedHour['start']))->timestamp;
                                $final = Carbon::parse(date('Y-m-d') . ' ' . $newDay['end'])->timestamp - (Carbon::parse(date('Y-m-d') . ' ' . $blockedHour['end']))->timestamp;

                                if ($initial <= $minimun_time_schedule * -3600 || $final >= $minimun_time_schedule * 3600) {
                                    // Se deja pasar
                                } else {
                                    // Si no hay suficiente espacio entre las horas
                                    return $this->errorResponse('Sus horas de inicio o fin deben de tener al menos una hora de espacio entre sus eventualidades, ' . $dayId, 422, $blockAgenda);
                                }
                            }
                        }
                    }
                }
            }

            $agendasAvailables = Agenda::where('iddoctor', $doctorId)
                ->availability()
                ->active();

            $agenda_review = $agendasAvailables->get();

            foreach ($agenda_review as $agenda) {

                $agendaDayHours = AgendaDaysHours::where('idagenda', $agenda->id)->active();
                foreach ($params['days_array'] as $newDay) {
                    $dayId = $newDay['day'];
                    $matchingDays = $agendaDayHours->where('idday', $dayId)->get();

                    if ($matchingDays->isNotEmpty()) {
                        $newStart = Carbon::parse(date('Y-m-d') . ' ' . $newDay['start']);
                        $newEnd = Carbon::parse(date('Y-m-d') . ' ' . $newDay['end']);

                        foreach ($matchingDays as $agendaDay) {
                            $registerStart = Carbon::parse(date('Y-m-d') . ' ' . $agendaDay->start_hour);
                            $registerEnd = Carbon::parse(date('Y-m-d') . ' ' . $agendaDay->end_hour);
                            $message = null;
                            if (($newStart >= $registerStart && $newStart < $registerEnd) &&
                                ($newEnd > $registerStart && $newEnd <= $registerEnd)
                            ) {
                                $message = 'inicial y final';
                            } elseif ($newStart >= $registerStart && $newStart < $registerEnd) {
                                $message = 'inicial';
                            } elseif ($newEnd > $registerStart && $newEnd <= $registerEnd) {
                                $message = 'final';
                            } elseif ($newStart < $registerStart && $newEnd > $registerEnd) {
                                $message = 'completo';
                            }

                            if (!is_null($message)) {
                                $dayName = Day::find($dayId, ['name'])->name;
                                $data = [
                                    'days' => [$dayName],
                                    'doctor_id' =>  $doctorId,
                                    'register' => AgendaResource::collection(
                                        Agenda::where('id', $agenda->id)->get()->load('agendaDaysHours.day')
                                    )
                                ];

                                return $this->errorResponse('Días repetidos en otro horario, con cruce en la hora ' . $message, 422, $data, false);
                            }
                        }
                    }
                }
            }

            return $this->successResponse('OK');
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function verificationDateUnAvailability($params)
    {
        try {
            $doctorId = $params['doctor_id'] ?? null;
            #Validación inicial
            $validation = Validator::make($params, [
                'full_day' => 'required|integer|min:0|max:1',
            ]);

            if ($validation->fails()) {
                return $this->errorResponse($validation->errors(), 422);
            }

            $full_day = $params['full_day'];
            $start_date_block = $params['start_date_block'];
            $end_date_block = $params['end_date_block'];
            $daysArrays = $params['days_array'] ?? [];

            $dateRange = [];

            if ($full_day == 0) {
                // Bloqueo parcial: recorrer días y horas específicos
                foreach ($daysArrays as $dayData) {
                    $dayOfWeek = $dayData['day']; // 1 = lunes, 7 = domingo
                    $currentDate = $start_date_block;

                    while (strtotime($currentDate) <= strtotime($end_date_block)) {
                        if ((int)date('N', strtotime($currentDate)) === $dayOfWeek) {
                            $dateRange[] = $currentDate;
                        }
                        $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
                    }
                }
            } else {
                // Bloqueo completo: incluir todas las fechas entre start y end
                $currentDate = $start_date_block;
                while (strtotime($currentDate) <= strtotime($end_date_block)) {
                    $dateRange[] = $currentDate;
                    $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
                }
            }

            # Buscar reservas activas dentro del rango
            $reservation = Reservation::where('iddoctor', $doctorId)
                ->whereIn('date', $dateRange)
                ->active()->get()->load('horary', 'patient');

            $busyReservation = [];

            if ($full_day == 0) {
                // Evaluar conflictos por horas específicas por día
                foreach ($reservation as $value) {
                    foreach ($daysArrays as $dayData) {
                        $dayOfWeek = $dayData['day'];
                        $start_hour = $dayData['start'];
                        $end_hour = $dayData['end'];

                        if (date('N', strtotime($value->date)) == $dayOfWeek) {
                            $horaryStart = $value['horary']->start;
                            $horaryEnd = $value['horary']->end;

                            if (!($horaryEnd < $start_hour || $horaryStart > $end_hour)) {
                                $busyReservation[] = $value;
                            }
                        }
                    }
                }
            } else {
                // Evaluar conflictos por bloque completo entre fecha + hora inicio/fin
                $startDateTime = Carbon::parse($start_date_block . ' ' . $params['start_hour_block']);
                $endDateTime = Carbon::parse($end_date_block . ' ' . $params['end_hour_block']);

                foreach ($reservation as $value) {
                    $resStart = Carbon::parse($value->date . ' ' . $value['horary']->start);
                    $resEnd = Carbon::parse($value->date . ' ' . $value['horary']->end);

                    if ($resEnd > $startDateTime && $resStart < $endDateTime) {
                        $busyReservation[] = $value;
                    }
                }
            }

            if (count($busyReservation) > 0) {
                if (isset($params['preview']) && $params['preview']) {
                    $detail['for_reschedule'] =  ReservationResource::collection($busyReservation);
                    return $this->errorResponse('Se encontraron detalles a validar.', 409, $detail);
                }

                $arrayIdsReservation = collect($busyReservation)->pluck('id')->toArray();
                dd('falta reagendar las reservas' . $arrayIdsReservation);
                // $validate = $this->rescheduleRerservationArray($arrayIdsReservation);
                // if (!$validate->original['status']) return $validate;

                // return $this->successResponse('Sus reservas han sido marcadas como por reagendar');
            }

            $detail['for_reschedule'] = ReservationResource::collection([]);

            return $this->successResponse('OK', $detail);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
