<?php

namespace App\Traits\AgendaHelpers;

use App\Enums\Status;
use App\Http\Resources\ReservationResource;
use App\Models\Agenda;
use App\Models\AgendaDaysHours;
use App\Models\Day;
use App\Models\Horary;
use App\Models\Reservation;
use App\Traits\Validates\ValidatesDayTrait;
use Carbon\Carbon;

trait ScheduleResetTrait
{
    use HoraryEditorTrait;
    use ValidatesDayTrait;

    protected function resetAgendaSchedules($idagenda, $params)
    {
        try {
            $doctorId  = $params['doctor_id'] ?? null;
            $daysArray = $params['days_array'];
            $agenda_review = Agenda::active()
                ->whereNot('id', $idagenda)
                ->where('iddoctor', $doctorId)
                ->availability()
                ->get();

            $validate = $this->validateNewDays($agenda_review, $daysArray);
            if (!$validate->original['status']) {
                return $validate;
            }

            $breakStatus        = $params['break_status'] ?? false;
            $breakStart         = $params['break_start'] ?? null;
            $breakEnd           = $params['break_end'] ?? null;
            $duration = $params['duration_hour'] ?? null;
            $wait_time = $params['wait_time_hour'] ?? null;
            $detail = [];

            #Verificar si se manda o no colacion / Usar configuracion guardada o parametros
            if ($breakStatus === true) {
                if (!isset($params['break_start']) && !isset($params['break_end'])) {
                    return $this->errorResponse('Es requerido enviar la hora inicio y fin del break ', 400);
                }
                if ($params['break_start'] < $params['break_end']) {
                    $breakStart = new Carbon($params['break_start']);
                    $breakEnd = new Carbon($params['break_end']);
                } else {
                    return $this->errorResponse('La hora de inicio del break debe de ser menor a la hora fin', 422);
                }
            }
            $detail['plus'] = [];
            $detail['minus'] = [];
            $detail['for_reschedule'] = [];
            $detail['general'] = [];
            $daysDeleted = [];
            $daysHoursIterativa = AgendaDaysHours::where('idagenda', $idagenda)->active()->get();
            foreach ($daysHoursIterativa as $key => $dayEC) {

                //paso 1: eliminamos los registros ya ingresados.
                $registerHoraryDelete = Horary::where('idagenda', $idagenda)
                    ->where('idday', $dayEC['idday'])
                    ->active()->get();

                foreach ($registerHoraryDelete as $key => $value) {
                    $value['color'] = 'error';
                    array_push($detail['minus'], $value);
                }

                //paso 2: buscamos reservas a ser reprogramadas
                if (count($detail['minus']) > 0) {
                    #Ver si hay reservas que ocupan registros a eliminar
                    $arrayIdHorarys = collect($detail['minus'])->map(function ($object) {
                        return $object->id;
                    })->toArray();

                    $reservation = Reservation::whereIn('idhorary', $arrayIdHorarys)
                        ->active()->get()
                        ->load('patient.rrhh', 'doctor.rrhh', 'horary');

                    if (isset($reservation[0]->id)) {
                        $detail['for_reschedule'] =  ReservationResource::collection($reservation);
                    }
                }
                $daysDeleted[] =  $dayEC['idday'];
            }
            //paso 3: pasamos a crear nuevos horarios
            if ($params['preview'] === true) {
                $response = $this->generateHorary($idagenda, $params);
                if (!$response->original['status']) {
                    return $response;
                }
                $horariesAdd = $response->original['data']['detail'];

                $dataPreview = [
                    'schedules' => $horariesAdd,
                    'for_reschedule' => $detail['for_reschedule']
                ];

                return $this->successResponse("Vista previa cargada", $dataPreview);
            }

            #Pasar reservar a Por reagendar
            if (count($detail['for_reschedule']) > 0) {
                # Actualizar cada reserva de manera indivual para que se mande su correo de reagendación
                $ids = collect($detail['for_reschedule'])->map(function ($item) {
                    // Si es un recurso (Resource), accede al modelo usando ->resource
                    return is_object($item) && property_exists($item, 'resource')
                        ? $item->resource->id
                        : $item->id;
                })->values()->all(); // Convierte en array plano
                dd('reservas por reagendar'  . $ids);
                // $validate = $this->rescheduleRerservationArray($ids);
                // if (!$validate->original['status']) {
                //     return $validate;
                // }
            }

            foreach ($detail['minus'] as $value) {
                Horary::where('id', $value->id)
                    ->update(['status' => Status::DELETED->value]);
            }

            $response = $this->generateHorary($idagenda, $params);
            if (!$response->original['status']) {
                return $response;
            }

            $insertData = [];
            foreach ($daysArray as $newDay) {
                $dayId = $newDay['day'];
                $insertData[] = [
                    'idagenda'      => $idagenda,
                    'idday'         => $dayId,
                    'start_hour'    => $newDay['start'],
                    'end_hour'      => $newDay['end'],
                    'created_at'    => now(),
                    'updated_at'    => now()
                ];
            }
            //eliminamos la relacion de la agenda con el dia
            AgendaDaysHours::whereIn('idday', $daysDeleted)
                ->where('idagenda', $idagenda)
                ->active()
                ->update(['status' => Status::DELETED->value]);

            // Realizamos la inserción masiva si tenemos datos
            if (!empty($insertData)) {
                AgendaDaysHours::insert($insertData);
            }

            //actualizamos los nuevos valores en la agenda
            Agenda::where('id', $idagenda)->active()
                ->update([
                    'break_start' => $breakStart,
                    'break_end' => $breakEnd,
                    'duration_hour' => $duration,
                    'wait_time_hour' => $wait_time
                ]);

            return $this->successResponse('Las operaciones fueron realizadas con éxito.');
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    protected function modifyAgendaHoursByDay($idagenda, $coincidences, $params, $daysArrayOriginal)
    {
        try {

            $doctorId  = $params['doctor_id'] ?? null;
            // Obtener las horas actuales de la agenda desde la base de datos
            $agendaDaysHours = AgendaDaysHours::where('idagenda', $idagenda)->active()->get();

            // Filtrar los días originales que coinciden con los días a modificar
            $daysCoincidences = collect($daysArrayOriginal)
                ->filter(fn($day) => in_array($day['day'], $coincidences))
                ->map(fn($day) => [
                    'day' => $day['day'],
                    'remove' => true,
                    'start_hour' => $day['start'],
                    'end_hour' => $day['end'],
                ])
                ->values();

            // Comparar con la agenda existente y detectar modificaciones
            $daysEditCoincidences = [];
            $daysIdsCoincidences = [];
            foreach ($daysCoincidences as $dayCoincidence) {
                $agendaDay = $agendaDaysHours->firstWhere('idday', $dayCoincidence['day']);

                if ($agendaDay) {
                    $valueStart = new Carbon($dayCoincidence['start_hour']);
                    $valueEnd = new Carbon($dayCoincidence['end_hour']);
                    $agendaStart = new Carbon($agendaDay['start_hour']);
                    $agendaEnd = new Carbon($agendaDay['end_hour']);

                    $startEdited = !$valueStart->equalTo($agendaStart);
                    $endEdited = !$valueEnd->equalTo($agendaEnd);

                    if ($startEdited || $endEdited) {
                        $daysEditCoincidences[] = [
                            'idday' => $dayCoincidence['day'],
                            'start_hour' => $dayCoincidence['start_hour'],
                            'end_hour' => $dayCoincidence['end_hour'],
                            'start_edit' => $startEdited,
                            'end_edit' => $endEdited,
                        ];
                        $daysIdsCoincidences[] = $dayCoincidence['day'];
                    }
                }
            }

            //daysEditCoincidences es un array de dias que coinciden con los días ya registrado en la base de datos, pero que han modificado sus horas

            //paso 2: validaciones.
            // 1:No choquen las horas a agregar con otras agendas del profesional.
            // 2: si se va a quitar horas, no haya reservaciones por atender.

            $agenda_review = Agenda::active()
                ->whereNot('id', $idagenda)
                ->where('iddoctor', $doctorId)
                ->availability()
                ->get();

            $validate = $this->validateNewDays($agenda_review, $daysArrayOriginal);
            if (!$validate->original['status']) {
                return $validate;
            }

            $breakStatus        = $params['break_status'] ?? false;
            $breakStart = null;
            $breakEnd = null;
            $duration = $params['duration_hour'];
            $wait_time = $params['wait_time_hour'];
            $detail = [];

            #Verificar si se manda o no colacion / Usar configuracion guardada o parametros
            if ($breakStatus === true) {
                if (!isset($params['break_start']) && !isset($params['break_end'])) {
                    return $this->errorResponse('Es requerido enviar la hora inicio y fin del break ', 400);
                }
                if ($params['break_start'] < $params['break_end']) {
                    $breakStart = new Carbon($params['break_start']);
                    $breakEnd = new Carbon($params['break_end']);
                } else {
                    return $this->errorResponse('La hora de inicio del break debe de ser menor a la hora fin', 422);
                }
            }
            $detail['plus'] = [];
            $detail['minus'] = [];
            $detail['for_reschedule'] = [];
            $detail['general'] = [];

            foreach ($daysEditCoincidences as $key => $dayEC) {
                $startEdit = new Carbon($dayEC['start_hour']);
                $endEdit = new Carbon($dayEC['end_hour']);
                $agendaDayHour = AgendaDaysHours::where('idagenda', $idagenda)
                    ->where('idday', $dayEC['idday'])
                    ->active()
                    ->first();
                $startAgendaDay = new Carbon($agendaDayHour->start_hour);
                $endAgendaDay = new Carbon($agendaDayHour->end_hour);
                $dayName = Day::where('id', $dayEC['idday'])->first()->name;
                if ($dayEC['start_edit'] && $dayEC['end_edit']) { # Edición de registro inicial y final
                    $minimumRiseTime = $wait_time + $duration;

                    if ($startEdit < $startAgendaDay) { // vamos a agregar horas

                        $endAgendaHours = $startAgendaDay->copy()->subMinutes($wait_time); //hora resultante de restar $wait_time minutos a $startAgendaDay
                        $extendedTime = $startEdit->diffInMinutes($startAgendaDay); //minutos de diferencia entre $startEdit y $startAgendaDay
                        #Verificar si cumple con la cantidad minima de tiempo de ampliación
                        if ($extendedTime >= $minimumRiseTime) {
                            $countRegisterNews = floor($extendedTime / $minimumRiseTime);
                            $nuevo_start = $startEdit->copy();
                            $nuevo_end = $startEdit->copy()->addMinutes($wait_time);

                            $division = false;

                            for ($i = 0; $i < $countRegisterNews; $i++) {
                                if (!$division) {
                                    $start_register = $nuevo_start;
                                    $end_register = $start_register->copy()->addMinutes($duration);
                                    $division = true;
                                } else {
                                    $start_register = $nuevo_end;
                                    $end_register = $start_register->copy()->addMinute($duration);
                                }

                                #El inicio de un registro no puede ser menor al registro que se quiere definir
                                if ($end_register > $endAgendaHours) {
                                    break;
                                }
                                $nuevo_start = $start_register;
                                $nuevo_end = $end_register;

                                # Verificar si entra en la colación
                                if ($breakStatus) {
                                    if (($nuevo_start >= $breakStart && $nuevo_start <= $breakEnd) || ($nuevo_end >= $breakStart && $nuevo_end <= $breakEnd)) {
                                        $nuevo_start = $breakEnd->copy();
                                        $nuevo_end = $nuevo_start->copy()->addMinutes($duration);
                                    }
                                }

                                $structureRegisterHorary = [
                                    'idday'     => $dayEC['idday'],
                                    'nameDay'   => $dayName,
                                    'start'     => $nuevo_start->format('H:i:s'),
                                    'end'       => $nuevo_end->format('H:i:s'),
                                    'duration'  => $duration,
                                    'wait_time' => $wait_time,
                                    'idagenda'  => $idagenda,
                                    'color'     => 'success',
                                    'status'    => Status::ACTIVE->value
                                ];
                                array_push($detail['plus'], $structureRegisterHorary);
                                $nuevo_end = $nuevo_end->copy()->addMinutes($wait_time);
                            }
                        }
                        // dd($extendedTime);

                    } else { // vamos a quitar horas
                        $registerHoraryDelete = Horary::where('idagenda', $idagenda)
                            ->where('idday', $dayEC['idday'])
                            ->where('start', '<', $startEdit)->active()->get();

                        foreach ($registerHoraryDelete as $key => $value) {
                            $value['color'] = 'error';
                            array_push($detail['minus'], $value);
                        }
                    }

                    if ($endEdit > $endAgendaDay) {

                        #Debo tomar el último registro que genero para el horario
                        $end_last_horary = Horary::where('idagenda', $idagenda)
                            ->where('idday', $dayEC['idday'])
                            ->latest('start')->active()->first();

                        # Tiempor que se puede extender
                        $extendedTime = $endEdit->diffInMinutes($endAgendaDay);
                        if ($extendedTime >= $minimumRiseTime) {
                            #Verificar si tendrá más de un registro
                            $countRegisterNews = floor($extendedTime / $minimumRiseTime);

                            $nuevo_start = Carbon::parse($end_last_horary->end)->addMinutes($wait_time);
                            $nuevo_end = $endEdit->copy();
                            $division = false;

                            for ($i = 0; $i < $countRegisterNews; $i++) {
                                if (!$division) {
                                    $start_register = $nuevo_start;
                                    $end_register = $start_register->copy()->addMinute($duration);
                                    $division = true;
                                } else {
                                    $start_register = $nuevo_start;
                                    $end_register = $start_register->copy()->addMinute($duration);
                                }
                                #El inicio de un registro no puede ser menor al registro que se quiere definir
                                if ($end_register > $endEdit) {
                                    break;
                                }


                                $nuevo_start = $start_register;
                                $nuevo_end = $end_register;

                                # Verificar si entra en la colación
                                if ($breakStatus) {
                                    if (($nuevo_start >= $breakStart && $nuevo_start <= $breakEnd) || ($nuevo_end >= $breakStart && $nuevo_end <= $breakEnd)) {
                                        $nuevo_start = $breakEnd->copy();
                                        $nuevo_end = $nuevo_start->copy()->addMinute($duration);
                                    }
                                }

                                $structureRegisterHorary = [
                                    'idday'     => $dayEC['idday'],
                                    'nameDay'   => $dayName,
                                    'start'     => $nuevo_start->format('H:i:s'),
                                    'end'       => $nuevo_end->format('H:i:s'),
                                    'duration'  => $duration,
                                    'wait_time' => $wait_time,
                                    'idagenda'  => $idagenda,
                                    'color'     => 'success',
                                    'status'    => Status::ACTIVE->value
                                ];
                                array_push($detail['plus'], $structureRegisterHorary);
                                $nuevo_start = $nuevo_end->copy()->addMinute($wait_time);
                            }
                        }
                    } else {
                        # Busco todos los registros de horary que van a ser eliminados
                        $registerHoraryDelete = Horary::where('idagenda', $idagenda)
                            ->where('idday', $dayEC['idday'])
                            ->where('end', '>', $endEdit)->active()->get();

                        foreach ($registerHoraryDelete as $key => $value) {
                            $value['color'] = 'error';
                            array_push($detail['minus'], $value);
                        }
                    }

                    if (count($detail['minus']) > 0) {
                        #Ver si hay reservas que ocupan registros a eliminar

                        $arrayIdHorarys = collect($detail['minus'])->map(function ($object) {
                            return $object->id;
                        })->toArray();

                        $reservation = Reservation::whereIn('idhorary', $arrayIdHorarys)
                            ->active()->get()->load('patient.rrhh', 'doctor.rrhh', 'horary');
                        if (isset($reservation[0]->id)) {
                            $detail['for_reschedule'] =  ReservationResource::collection($reservation);
                        }
                    }
                } elseif ($dayEC['start_edit']) { # Edición de registro inicial
                    $minimumRiseTime = $wait_time + $duration;
                    if ($startEdit < $startAgendaDay) { // vamos a agregar horas

                        $endAgendaHours = $startAgendaDay->copy()->subMinutes($wait_time); //hora resultante de restar $wait_time minutos a $startAgendaDay
                        $extendedTime = $startEdit->diffInMinutes($startAgendaDay); //minutos de diferencia entre $startEdit y $startAgendaDay
                        #Verificar si cumple con la cantidad minima de tiempo de ampliación
                        if ($extendedTime >= $minimumRiseTime) {
                            $countRegisterNews = floor($extendedTime / $minimumRiseTime);
                            $nuevo_start = $startEdit->copy();
                            $nuevo_end = $startEdit->copy()->addMinutes($wait_time);

                            $division = false;

                            for ($i = 0; $i < $countRegisterNews; $i++) {
                                if (!$division) {
                                    $start_register = $nuevo_start;
                                    $end_register = $start_register->copy()->addMinutes($duration);
                                    $division = true;
                                } else {
                                    $start_register = $nuevo_end;
                                    $end_register = $start_register->copy()->addMinute($duration);
                                }

                                #El inicio de un registro no puede ser menor al registro que se quiere definir
                                if ($end_register > $endAgendaHours) {
                                    break;
                                }
                                $nuevo_start = $start_register;
                                $nuevo_end = $end_register;

                                # Verificar si entra en la colación
                                if ($breakStatus) {
                                    if (($nuevo_start >= $breakStart && $nuevo_start <= $breakEnd) || ($nuevo_end >= $breakStart && $nuevo_end <= $breakEnd)) {
                                        $nuevo_start = $breakEnd->copy();
                                        $nuevo_end = $nuevo_start->copy()->addMinutes($duration);
                                    }
                                }

                                $structureRegisterHorary = [
                                    'idday'     => $dayEC['idday'],
                                    'nameDay'   => $dayName,
                                    'start'     => $nuevo_start->format('H:i:s'),
                                    'end'       => $nuevo_end->format('H:i:s'),
                                    'duration'  => $duration,
                                    'wait_time' => $wait_time,
                                    'idagenda'  => $idagenda,
                                    'color'     => 'success',
                                    'status'    => Status::ACTIVE->value
                                ];
                                array_push($detail['plus'], $structureRegisterHorary);
                                $nuevo_end = $nuevo_end->copy()->addMinutes($wait_time);
                            }
                        }
                        // dd($extendedTime);
                    } else {
                        # Busco todos los registros de horary que van a ser eliminados
                        $registerHoraryDelete = Horary::where('idagenda', $idagenda)
                            ->where('idday', $dayEC['idday'])
                            ->where('start', '<', $startEdit)->active()->get();

                        foreach ($registerHoraryDelete as $key => $value) {
                            $value['color'] = 'error';
                            array_push($detail['minus'], $value);
                        }
                    }

                    if (count($detail['minus']) > 0) {
                        #Ver si hay reservas que ocupan registros a eliminar

                        $arrayIdHorarys = collect($detail['minus'])->map(function ($object) {
                            return $object->id;
                        })->toArray();

                        $reservation = Reservation::whereIn('idhorary', $arrayIdHorarys)
                            ->active()->get()->load('patient.rrhh', 'doctor.rrhh', 'horary');
                        if (isset($reservation[0]->id)) {
                            $detail['for_reschedule'] =  ReservationResource::collection($reservation);
                        }
                    }
                } elseif ($dayEC['end_edit']) { # Edición de registro final
                    $minimumRiseTime = $wait_time + $duration;
                    if ($endEdit > $endAgendaDay) {
                        #Debo tomar el último registro que genero para el horario
                        $end_last_horary = Horary::where('idagenda', $idagenda)
                            ->where('idday', $dayEC['idday'])
                            ->latest('start')->active()->first();
                        # Tiempor que se puede extender
                        $extendedTime = $endEdit->diffInMinutes($endAgendaDay);

                        if ($extendedTime >= $minimumRiseTime) {
                            #Verificar si tendrá más de un registro
                            $countRegisterNews = floor($extendedTime / $minimumRiseTime);

                            $nuevo_start = Carbon::parse($end_last_horary->end)->addMinutes($wait_time);
                            $nuevo_end = $endEdit->copy();
                            $division = false;

                            for ($i = 0; $i < $countRegisterNews; $i++) {
                                if (!$division) {
                                    $start_register = $nuevo_start;
                                    $end_register = $start_register->copy()->addMinute($duration);
                                    $division = true;
                                } else {
                                    $start_register = $nuevo_start;
                                    $end_register = $start_register->copy()->addMinute($duration);
                                }
                                #El inicio de un registro no puede ser menor al registro que se quiere definir
                                if ($end_register > $endEdit) {
                                    break;
                                }
                                $nuevo_start = $start_register;
                                $nuevo_end = $end_register;
                                # Verificar si entra en la colación

                                if ($breakStatus) {
                                    if (($nuevo_start >= $breakStart && $nuevo_start <= $breakEnd) || ($nuevo_end >= $breakStart && $nuevo_end <= $breakEnd)) {
                                        $nuevo_start = $breakEnd->copy();
                                        $nuevo_end = $nuevo_start->copy()->addMinute($duration);
                                    }
                                }

                                $structureRegisterHorary = [
                                    'idday'     => $dayEC['idday'],
                                    'nameDay'   => $dayName,
                                    'start'     => $nuevo_start->format('H:i:s'),
                                    'end'       => $nuevo_end->format('H:i:s'),
                                    'duration'  => $duration,
                                    'wait_time' => $wait_time,
                                    'idagenda'  => $idagenda,
                                    'color'     => 'success',
                                    'status'    => Status::ACTIVE->value
                                ];
                                array_push($detail['plus'], $structureRegisterHorary);
                                $nuevo_start = $nuevo_end->copy()->addMinute($wait_time);
                            }
                        }
                    } else {
                        # Busco todos los registros de horary que van a ser eliminados
                        $registerHoraryDelete = Horary::where('idagenda', $idagenda)
                            ->where('idday', $dayEC['idday'])
                            ->where('end', '>', $endEdit)->active()->get();

                        foreach ($registerHoraryDelete as $key => $value) {
                            $value['color'] = 'error';
                            array_push($detail['minus'], $value);
                        }
                    }


                    if (count($detail['minus']) > 0) {
                        #Ver si hay reservas que ocupan registros a eliminar
                        $arrayIdHorarys = collect($detail['minus'])->map(function ($object) {
                            return $object->id;
                        })->toArray();

                        $reservation = Reservation::whereIn('idhorary', $arrayIdHorarys)
                            ->active()->get()
                            ->load('patient.rrhh', 'doctor.rrhh', 'horary');
                        if (isset($reservation[0]->id)) {
                            $detail['for_reschedule'] =  ReservationResource::collection($reservation);
                        }
                    }
                }
            }

            if ($params['preview'] === true) {
                $horariesAgenda = Horary::whereIn('idday', $daysIdsCoincidences)
                    ->where('idagenda', $idagenda)
                    ->active()
                    ->get();
                // Inicializa general con horarios actuales + color
                $general = [];
                foreach ($horariesAgenda as $value) {
                    $valueArray = $value->toArray();
                    $valueArray['color'] = 'info';
                    $valueArray['nameDay'] = $value->day->name ?? null;
                    $general[] = $valueArray;
                }

                // Eliminar los que están en minus
                if (!empty($detail['minus']) && is_array($detail['minus'])) {
                    $minusIds = array_column($detail['minus'], 'id');
                    $general = array_filter($general, function ($item) use ($minusIds) {
                        return !in_array($item['id'], $minusIds);
                    });
                }

                // Agregar los plus
                if (!empty($detail['plus']) && is_array($detail['plus'])) {
                    foreach ($detail['plus'] as $plus) {
                        $plus['color'] = 'success';
                        $general[] = $plus;
                    }
                }

                // 🔥 Agregar los días adicionales que están en coincidences pero no en daysIdsCoincidences
                $otherDayIds = array_diff($coincidences, $daysIdsCoincidences);

                if (!empty($otherDayIds)) {
                    $otherHoraries = Horary::whereIn('idday', $otherDayIds)
                        ->where('idagenda', $idagenda)
                        ->active()
                        ->get();
                    foreach ($otherHoraries as $value) {
                        $valueArray = $value->toArray();
                        $valueArray['color'] = 'secondary';
                        $valueArray['nameDay'] = $value->day->name ?? null;
                        $general[] = $valueArray;
                    }
                }

                // Ordenar por idday y luego por start
                usort($general, function ($a, $b) {
                    if ($a['idday'] == $b['idday']) {
                        return strtotime($a['start']) - strtotime($b['start']);
                    }
                    return $a['idday'] - $b['idday'];
                });

                // Resetear índices
                $detail['general'] = array_values($general);

                // Retornar respuesta
                return $this->successResponse('Se encontraron detalles a validar.', $detail);
            }
            #Pasar reservar a estado  Por reagendar
            if (count($detail['for_reschedule']) > 0) {
                # Actualizar cada reserva de manera indivual para que se mande su correo de reagendación

                $ids = collect($detail['for_reschedule'])->map(function ($item) {
                    // Si es un recurso (Resource), accede al modelo usando ->resource
                    return is_object($item) && property_exists($item, 'resource')
                        ? $item->resource->id
                        : $item->id;
                })->values()->all(); // Convierte en array plano

                dd('Reservas por reagenda' . $ids);
                // $validate = $this->rescheduleRerservationArray($ids);
                // if (!$validate->original['status']) {
                //     return $validate;
                // }
            }

            #Agregar nuevos registros para horary
            if (count($detail['plus']) > 0) {
                $dataToInsert = array_map(function ($item) {
                    unset($item['color'], $item['nameDay']); // Elimina ambos campos
                    return $item;
                }, $detail['plus']);

                Horary::insert($dataToInsert);
            }

            if (count($detail['minus']) > 0) {
                foreach ($detail['minus'] as $value) {
                    // Debugging
                    Horary::where('id', $value->id)
                        ->update(['status' => Status::DELETED->value]);
                }
            }
            if (count($daysEditCoincidences) > 0) {
                foreach ($daysEditCoincidences as $key => $dayEC) {
                    AgendaDaysHours::where('idday', $dayEC['idday'])
                        ->where('idagenda', $idagenda)
                        ->active()
                        ->update([
                            'start_hour' => $dayEC['start_hour'],
                            'end_hour' => $dayEC['end_hour'],
                        ]);
                }
            }


            return $this->successResponse('Las operaciones fueron realizadas con éxito.');
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
