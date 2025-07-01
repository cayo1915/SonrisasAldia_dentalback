<?php

namespace App\Services;

use App\Models\Agenda;
use App\Models\AgendaDaysHours;
use App\Models\Day;
use App\Models\Horary;
use App\Models\Reservation;
use App\Traits\HasResponse;
use App\Traits\Validates\ValidatesAgenda;
use App\Traits\Validates\ValidatesHorary;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class HoraryService
{
    use HasResponse;
    use ValidatesAgenda;
    use ValidatesHorary;

    public function availableTimes($params)
    {
        try {
            $doctorId  = $params['doctor_id'] ?? null;
            $agendas = Agenda::where('iddoctor', $doctorId)
                ->whereHas('horary', function ($q) {
                    $q->active();
                })->availability()->active();

            if (isset($params['idagenda'])) {
                $agendas = $agendas->where('id', $params['idagenda']);
            }

            $agendas = $agendas->get();

            $dataAllHorary = [];
            for ($i = 0; $i < count($agendas); $i++) {
                $dataIterada = $this->getHoraryAvailable($agendas[$i]->id, $params);
                $dataIterada = $dataIterada->original['data']['detail'];
                for ($j = 0; $j < count($dataIterada); $j++) {
                    array_push($dataAllHorary, $dataIterada[$j]);
                }
            }

            #Contar la cantidad de horarios frente a la cantidad de disponibles para añadir como elemento
            $countAvailables = [];
            for ($i = 0; $i < count($dataAllHorary); $i++) {
                $countAvailables[$dataAllHorary[$i]->date] = isset($countAvailables[$dataAllHorary[$i]->date]['countFree'])
                    ? $countAvailables[$dataAllHorary[$i]->date]
                    : ['countTotal' => 0, 'countFree' => 0];

                if ($dataAllHorary[$i]->statusAvailable == true) {

                    $countAvailables[$dataAllHorary[$i]->date]['countFree'] = $countAvailables[$dataAllHorary[$i]->date]['countFree'] + 1;
                }

                $countAvailables[$dataAllHorary[$i]->date]['countTotal'] = $countAvailables[$dataAllHorary[$i]->date]['countTotal'] + 1;
            }

            for ($i = 0; $i < count($dataAllHorary); $i++) {
                foreach (array_keys($countAvailables) as $key) {
                    if ($dataAllHorary[$i]->date == $key) {
                        $total = $countAvailables[$key]['countTotal'];
                        $free = $countAvailables[$key]['countFree'];
                        $dataAllHorary[$i]->statusCompleted = true;
                        if ($total * $free == 0) {
                            $dataAllHorary[$i]->statusCompleted = false;
                        }
                        $dataAllHorary[$i]->countAvailable = "$free/$total";
                    }
                }
            }

            usort($dataAllHorary, function ($a, $b) {
                return strcmp($a->start, $b->start) ?: strcmp($a->end, $b->end);
            });
            return $this->successResponse('Lectura exitosa',  $dataAllHorary);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getHoraryAvailable($id, $params, $only = null)
    {
        try {
            #Pasar a array la data
            $dataHorary = $this->getHorary($id, $params);
            $dataHorary = json_decode(json_encode($dataHorary->original['data']['detail']));
            $month = isset($params['month']) ? $params['month'] : Carbon::now()->format('m');
            $year = isset($params['year']) ? $params['year'] : Carbon::now()->format('Y');

            $dateParamsStart = "$year-$month-01";

            #Buscar si hay agendas de tipo de no disponibilidad
            $agendaNotAvailable = Agenda::active()
                ->unAvailability()
                ->where('iddoctor', $params['doctor_id'])->get();

            $dataAvailable = $dataHorary;

            #De acuerdo a la cantidad de registros encontrados se harán los recortes a la data de acuerdo l horaario de no disponibilidad
            if (count($dataHorary) != 0) {
                if (count($agendaNotAvailable) > 0) {

                    $dataAvailableNoData = false;
                    for ($i = 0; $i < count($agendaNotAvailable); $i++) {

                        #En caso de tener más de un horario disponible la data se tiene que ir actualizando
                        if (!$dataAvailableNoData && $i > 0) {
                            $dataHorary = $dataAvailable;
                        }
                        $dataAvailableNoData = false;

                        #Registros a considerar
                        $agendaNotAvailable2 = $agendaNotAvailable[$i];
                        $month_start    = Carbon::parse($agendaNotAvailable2->start_date_block)->format('m');
                        $year_start     = Carbon::parse($agendaNotAvailable2->start_date_block)->format('Y');
                        $month_end      = Carbon::parse($agendaNotAvailable2->end_date_block)->format('m');
                        $year_end       = Carbon::parse($agendaNotAvailable2->end_date_block)->format('Y');

                        #Cantidad de días del mes del registro fin de la agenda
                        $countDaysMonthRegisterEnd = cal_days_in_month(CAL_GREGORIAN, $month_end, $year_end);

                        $dateOnlyMonthYearStart = "$year_start-$month_start-01";
                        $dateOnlyMonthYearEnd = "$year_end-$month_end-$countDaysMonthRegisterEnd";

                        #Verificar que el mes esta dentro del rango de la agenda
                        if (strtotime($dateParamsStart) >= strtotime($dateOnlyMonthYearStart) && strtotime($dateParamsStart) <= strtotime($dateOnlyMonthYearEnd)) {
                            $dataAvailable = $this->dataHoraryAvailable($agendaNotAvailable2, $dataHorary);
                        } elseif ($i == 0) {
                            $dataAvailable = $dataHorary;
                        } else {
                            $dataAvailableNoData = true;
                        }
                    }
                }
            }

            $reservation = Reservation::where('iddoctor', $params['doctor_id'])
                ->active();

            if ($only == 1) {
                $reservation = $reservation->whereHas('horary.agenda', function ($q) use ($id) {
                    $q->where('id', $id);
                });
            } elseif (is_null($only)) {
                $allAgendaProfessional = Agenda::where('iddoctor', $params['doctor_id'])
                    ->active()
                    ->pluck('id');
                $reservation = $reservation->whereHas('horary.agenda', function ($q) use ($allAgendaProfessional) {
                    $q->whereIn('id', $allAgendaProfessional);
                });
            }

            $reservation = $reservation->get()->load(
                'patient',
                'saleOrders'
            );

            if (count($reservation) > 0) {
                $dataAvailable = $this->dataHoraryWithReservations($dataAvailable, $reservation);
            }
            return $this->successResponse('Lectura exitosa', $dataAvailable);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function dataHoraryAvailable($agendaNotAvailable2, $dataHorary)
    {
        try {
            $dataHorary2 = $dataHorary;
            $date_start = Carbon::parse($agendaNotAvailable2->start_date_block);
            $date_end   = Carbon::parse($agendaNotAvailable2->end_date_block);

            $dateMiddle = $date_start;
            $dateRangeAgendaNoAvailable = [];
            #Obtener todas las fechas dentro del rango de los parametros de la agend de no disponbilidad
            #Dependiendo si tiene su registro días con datos o sin datos
            if (!$agendaNotAvailable2->full_day) {
                // Recuperar bloques horarios específicos por día
                $detalleHoras = AgendaDaysHours::where('idagenda', $agendaNotAvailable2->id)
                    ->active()
                    ->get();

                foreach ($detalleHoras as $detalle) {
                    if (Carbon::parse($date_start)->isoWeekday() == $detalle->idday) {
                        array_push($dateRangeAgendaNoAvailable, date('Y-m-d', strtotime($date_start)));
                    }
                }

                while (strtotime($date_end) >= strtotime($date_start)) {
                    if (strtotime($date_end) != strtotime($dateMiddle)) {
                        $dateMiddle = date('Y-m-d', strtotime($dateMiddle . " + 1 day"));
                        foreach ($detalleHoras as $detalle) {
                            if (Carbon::parse($dateMiddle)->isoWeekday() == $detalle->idday) {
                                array_push($dateRangeAgendaNoAvailable, $dateMiddle);
                            }
                        }
                    } else {
                        break;
                    }
                }
            } else {
                array_push($dateRangeAgendaNoAvailable, date('Y-m-d', strtotime($date_start)));
                while (strtotime($date_end) >= strtotime($date_start)) {
                    if (strtotime($date_end) != strtotime($dateMiddle)) {
                        $dateMiddle = date('Y-m-d', strtotime($dateMiddle . " + 1 day"));
                        array_push($dateRangeAgendaNoAvailable, $dateMiddle);
                    } else {
                        break;
                    }
                }
            }
            for ($i = 0; $i < count($dateRangeAgendaNoAvailable); $i++) {
                #Genero la fecha para remplazar todos los registros donde esten dicha fecha
                $date = Carbon::parse($dateRangeAgendaNoAvailable[$i])->toDateString();
                for ($j = 0; $j < count($dataHorary); $j++) {

                    if (strtotime($dataHorary[$j]->date) < strtotime(now()) && strtotime($dataHorary[$j]->date . ' ' . $dataHorary[$j]->start) < strtotime($dataHorary[$j]->date . ' ' . $dataHorary[$j]->start)) {
                    }
                    if ($dataHorary[$j]->date == $date) {

                        if ($agendaNotAvailable2->start_date_block <= $date && $agendaNotAvailable2->end_date_block >= $date) {
                            if ($agendaNotAvailable2->full_day == 0) {
                                $idday = Carbon::parse($date)->isoWeekday(); // 1 (Lunes) a 7 (Domingo)

                                $dayHours = AgendaDaysHours::where('idagenda', $agendaNotAvailable2->id)
                                    ->where('idday', $idday)
                                    ->active()
                                    ->get();

                                foreach ($dayHours as $hour) {
                                    $horaryStart = strtotime($dataHorary[$j]->start);
                                    $horaryEnd = strtotime($dataHorary[$j]->end);
                                    $unavailableStart = strtotime($hour->start_hour);
                                    $unavailableEnd = strtotime($hour->end_hour);

                                    if ($horaryEnd > $unavailableStart && $horaryStart < $unavailableEnd) {
                                        // Hay solapamiento, marcar como no disponible
                                        $dataHorary2[$j]->statusAvailable      = false;
                                        $dataHorary2[$j]->OnAgendaNotAvailable = true;
                                        $dataHorary2[$j]->comment              = $agendaNotAvailable2->comment;
                                        $dataHorary2[$j]->color                = "7414BB";

                                        break; // No es necesario seguir verificando más rangos para ese horario
                                    }
                                }
                            } elseif ($agendaNotAvailable2->full_day) {
                                // Si es el primer o último día del rango
                                if ($date == $agendaNotAvailable2->start_date_block || $date == $agendaNotAvailable2->end_date_block) {

                                    $startHourBlock = $agendaNotAvailable2->start_hour_block; // Se puede ajustar si el rango cambia dinámicamente
                                    $endHourBlock = $agendaNotAvailable2->end_hour_block;

                                    $horaryStart = strtotime($dataHorary[$j]->start);
                                    $horaryEnd = strtotime($dataHorary[$j]->end);
                                    $partialStart = strtotime($startHourBlock);
                                    $partialEnd = strtotime($endHourBlock);

                                    if (
                                        ($date == $agendaNotAvailable2->start_date_block && $horaryEnd <= $partialStart) ||
                                        ($date == $agendaNotAvailable2->end_date_block && $horaryStart >= $partialEnd)
                                    ) {
                                        // El bloque horario está fuera del rango de bloqueo parcial => lo dejo pasar
                                    } else {
                                        // Hay solapamiento con el rango parcial => bloquear
                                        $dataHorary2[$j]->statusAvailable       = false;
                                        $dataHorary2[$j]->OnAgendaNotAvailable  = true;
                                        $dataHorary2[$j]->comment               = $agendaNotAvailable2->comment;
                                        $dataHorary2[$j]->color                 = "7414BB";
                                    }
                                } else {
                                    // Día intermedio: bloquear todo el horario del día
                                    $dataHorary2[$j]->statusAvailable       = false;
                                    $dataHorary2[$j]->OnAgendaNotAvailable  = true;
                                    $dataHorary2[$j]->comment               = $agendaNotAvailable2->comment;
                                    $dataHorary2[$j]->color                 = "7414BB";
                                }
                            }
                        }
                    }
                }
            }
            return $dataHorary2;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function dataHoraryWithReservations($dataAvailable, $reservation)
    {
        try {
            $dataAvailable2 = $dataAvailable;
            for ($i = 0; $i < count($reservation); $i++) {
                for ($j = 0; $j < count($dataAvailable); $j++) {

                    if ($dataAvailable[$j]->date == $reservation[$i]->date && $dataAvailable[$j]->id == $reservation[$i]->idhorary) {

                        #Remplazo data
                        $dataAvailable2[$j]->statusAvailable            = false;
                        $dataAvailable2[$j]->idreservation              = $reservation[$i]->id;
                        $dataAvailable2[$j]->status_reservation         = $reservation[$i]->status;
                        $dataAvailable2[$j]->occupiedByID               = $reservation[$i]->idpatient;
                        // $dataAvailable2[$j]->occupiedBy                 = isset($reservation[$i]['customer']->name) ? $reservation[$i]['customer']->name . ' ' . $reservation[$i]['customer']->last_name : 'Sin nombre';
                        // $dataAvailable2[$j]->occupiedMail               = $reservation[$i]['customer']->mail ?? 'Sin email';
                        $dataAvailable2[$j]->saleOrders                 = $reservation[$i]['saleOrders'] ?? [];
                        $dataAvailable2[$j]->idservice_type             = $reservation[$i]->idservice_type;
                        $dataAvailable2[$j]->type_modality              = $reservation[$i]->type_modality;
                        $dataAvailable2[$j]->status_customer            = $reservation[$i]->status_customer;
                        $dataAvailable2[$j]->type                       = $reservation[$i]->type;
                    }
                }
            }
            return $dataAvailable2;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function getHorary($id, $params)
    {
        try {
            $dataAvailable = [];
            $month = isset($params['month']) ? $params['month'] : Carbon::now()->format('m');
            $year = isset($params['year']) ? $params['year'] : Carbon::now()->format('Y');
            $date_start_week = isset($params['date_start_week']) ? $params['date_start_week'] : null;
            $date_end_week = isset($params['date_end_week']) ? $params['date_end_week'] : null;

            #Cantidad de días del mes de la fecha los parametros
            $countDaysMonthdateParams = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $dateParamsStart = !is_null($date_start_week) ? $date_start_week : "$year-$month-01";
            $dateParamsEnd = !is_null($date_end_week) ? $date_end_week : "$year-$month-$countDaysMonthdateParams";

            #Registros a listar en el calendario
            $agenda = Agenda::where('id', $id)
                ->active();

            $agenda = $agenda->with('horary')->get();
            if (count($agenda) == 0) {
                return $this->successResponse('Lectura exitosa', []);
            }

            $dataAvailable = $this->dataHorary($dateParamsStart, $dateParamsEnd, $agenda);

            #Obtener las llaves de todos los registros que se mostrarían en el calendario
            $arrayKeyDataAvailable = [];
            for ($i = 0; $i < count($dataAvailable); $i++) {
                array_push($arrayKeyDataAvailable, key($dataAvailable[$i]));
            }

            /* Transformar los arrays de distintas fechas en un solo objeto */
            $dateInKey =  array_keys(array_flip($arrayKeyDataAvailable));
            $arrayCalendar = [];

            for ($i = 0; $i < count($dataAvailable); $i++) {
                for ($j = 0; $j < count($dateInKey); $j++) {
                    $dates = isset($dataAvailable[$i][$dateInKey[$j]]) ? $dataAvailable[$i][$dateInKey[$j]] : null;
                    if (!is_null($dates)) {
                        for ($k = 0; $k < count($dates); $k++) {
                            array_push($arrayCalendar, $dates[$k]);
                        }
                    }
                }
            }
            if (count($arrayCalendar) > 0) {
                usort($arrayCalendar, function ($a, $b) {
                    return strtotime($a['start']) - strtotime($b['start']);
                });
            }
            return $this->successResponse('Lectura exitosa', $arrayCalendar);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function dataHorary($dateParamsStart, $dateParamsEnd, $agenda)
    {
        try {
            $horaryFinal = [];
            // $daysAgenda = json_decode($agenda[0]->days);
            $daysAgenda = $agenda[0]->horary->pluck('idday')->unique()->values();

            $dateMiddle = $dateParamsStart;
            $dateRangeParams = [];

            # Obtener todas las fechas dentro del rango de los parametros
            array_push($dateRangeParams, date('Y-m-d', strtotime($dateParamsStart)));

            while (strtotime($dateParamsEnd) >= strtotime($dateParamsStart)) {
                if (strtotime($dateParamsEnd) != strtotime($dateMiddle)) {
                    $dateMiddle = date('Y-m-d', strtotime($dateMiddle . " + 1 day"));
                    array_push($dateRangeParams, $dateMiddle);
                } else {
                    break;
                }
            }

            #Obtener las fechas que son ocupadas por la agenda
            $dateRangeAgenda = [];
            foreach ($dateRangeParams as $value) {
                array_push($dateRangeAgenda, $value);
            }

            for ($i = 0; $i < count($dateRangeAgenda); $i++) {

                #Genero la fecha para obtener que día de la semana es
                $date = Carbon::parse($dateRangeAgenda[$i])->toDateString();
                $dateWeek = Carbon::parse($dateRangeAgenda[$i])->isoWeekday();
                for ($j = 0; $j < count($daysAgenda); $j++) {
                    if ($dateWeek == $daysAgenda[$j]) {
                        $insertHorary = Horary::where('idagenda', $agenda[0]->id)
                            ->where('idday', $dateWeek)
                            ->active()
                            ->get()->toArray();

                        foreach (array_keys($insertHorary) as $key) {
                            $insertHorary[$key]['visible'] = Carbon::parse($date . ' ' . $insertHorary[$key]['start']) > Carbon::now() ? true : false;
                            $insertHorary[$key]['date'] = $date;
                            $insertHorary[$key]['color_agenda'] = $agenda[0]->color_agenda;
                            $insertHorary[$key]['statusAvailable'] = true;
                            $insertHorary[$key]['agenda'] = $agenda[0]->name;
                            $insertHorary[$key]['color'] = '54FA5E';
                            $insertHorary[$key]['modality'] = $agenda[0]->modality;
                        }
                        $insertHorary = [$date => $insertHorary];
                        array_push($horaryFinal, $insertHorary);
                    }
                }
            };
            return $horaryFinal;
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function automaticHorary($idagenda, $params)
    {
        DB::beginTransaction();
        try {

            #Validar si el profesional logueado le pertenece el idagenda que se recibe
            $validate = $this->validateAgenda($idagenda, $params);
            if (!$validate->original['status']) {
                return $validate;
            }

            $breakStatus = $params['break_status'] ?? false;
            $breakStart = null;
            $breakEnd = null;
            $eventualidadAgenda = null;
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();
            $doctorId = $params['doctor_id'] ?? null;

            #Buscar si hay eventualidades que se crucen con el mes actual
            $eventualidadAgendaQuery = Agenda::where('iddoctor', $doctorId)
                ->unAvailability()
                ->active()
                ->where(function ($query) use ($startOfMonth) {
                    $query->where('start_date_block', '<=', $startOfMonth)
                        ->where('end_date_block', '>=', $startOfMonth);
                })
                ->orWhere(function ($query) use ($endOfMonth) {
                    $query->where('start_date_block', '<=', $endOfMonth)
                        ->where('end_date_block', '>=', $endOfMonth);
                })->get();

            if (count($eventualidadAgendaQuery) > 0) {
                $eventualidadAgenda = $eventualidadAgendaQuery;
            }

            #Verificar si se manda o no break / Usar configuracion guardada o parametros
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

            #Validacion interna
            $validation = Validator::make($params, [
                'duration_hour' =>   'required|integer',
                'wait_time_hour' =>  'required|integer',
            ]);

            if ($validation->fails()) {
                return $this->errorResponse($validation->errors(), 422);
            }

            $duration = $params['duration_hour'];
            $wait_time = $params['wait_time_hour'];
            $daysArray = $params['days_array'];

            $arrayHorary = [];

            foreach ($daysArray as $dayEntry) {
                $dayId = $dayEntry['day'];
                $dayName = Day::where('id', $dayId)->first()->name;
                $startRegister = new Carbon($dayEntry['start']);
                $endRegister = new Carbon($dayEntry['end']);
                $validateTime = $this->validateTime($dayEntry, $params);
                if (!$validateTime->original['status']) {
                    return $validateTime;
                }

                $start = 0;
                $j = 0;
                $collation = false;
                $collationStartHour = false;

                while (!is_null($start)) {
                    if ($j == 0) {
                        $startHour = $startRegister->copy();
                        $endHour = $startRegister->copy()->addMinutes($duration);
                    }
                    /* Apartir de la segunda iteración empieza la suma */
                    if ($j != 0) {
                        if ($collation && !$collationStartHour) {
                            $startHour = $endHour;
                            $endHour = $startHour->copy()->addMinutes($duration);
                            $collationStartHour = true;
                        } else {
                            $startHour = $endHour->copy()->addMinute($wait_time);
                            $endHour = $startHour->copy()->addMinutes($duration);
                        }
                    }

                    if ($breakStatus === true) {
                        if ($endHour > $breakStart && !$collation) {
                            $endHour = $breakEnd->copy();
                            $collation = true;
                        } else {
                            $arrayHorary[] = [
                                'idday' => $dayId,
                                'nameDay' => $dayName,
                                'start' => $startHour->toTimeString(),
                                'end' => $endHour->toTimeString(),
                                'duration' => $duration,
                                'wait_time' => $wait_time,
                                'idagenda' => $idagenda,
                            ];
                        }
                    } else {
                        $arrayHorary[] = [
                            'idday' => $dayId,
                            'nameDay' => $dayName,
                            'start' => $startHour->toTimeString(),
                            'end' => $endHour->toTimeString(),
                            'duration' => $duration,
                            'wait_time' => $wait_time,
                            'idagenda' => $idagenda,
                        ];
                    }

                    if ($endRegister < $endHour->copy()->addMinutes($duration)) {
                        $start = null;
                        break;
                    }
                    $j++;
                }
            }
            if ($params['preview'] === true) {
                return $this->successResponse('Vista previa cargada', $arrayHorary, 200, $eventualidadAgenda);
            }
            $horary = [];
            foreach ($arrayHorary as $row) {
                $array = [
                    'idday'             =>  $row['idday'],
                    'start'             =>  $row['start'],
                    'end'               =>  $row['end'],
                    'duration'          =>  $row['duration'],
                    'wait_time'         =>  $row['wait_time'],
                    'idagenda'          =>  $row['idagenda'],
                    'created_at'        =>  Carbon::now(),
                    'updated_at'        =>  Carbon::now()
                ];
                array_push($horary, $array);
            }
            #Previsualización de agenda ficticias
            if ($params['preview'] === true) {
                return $this->successResponse('No puede crear horarios en modo previsualización');
            }
            $horary = Horary::insert($horary);
            DB::commit();
            return $this->successResponse('Horarios creados con éxito');
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function validateHoursCustom($params)
    {
        try {
            $horaryCustom = [];
            #Obtener todos los parametros que se mandan
            for ($i = 0; $i < $params['count_division']; $i++) {

                $horaryCustom['start' . $i] = new Carbon($params['start' . $i]);
                $horaryCustom['end' . $i] = new Carbon($params['end' . $i]);
            }

            if (count($horaryCustom) != ($params['count_division'] * 2)) {
                return $this->errorResponse('Las cantidad de horas debe de coincidir con su división seleccionada', 422);
            }
            #Verificar que no haya cruce entre los horarios
            for ($j = 0; $j < $params['count_division']; $j++) {
                $start = $horaryCustom['start' . $j];
                $end = $horaryCustom['end' . $j];

                #Que las horas fin no sean menor a sus horas inicio
                if ($end <= $start) {
                    return $this->errorResponse('La hora fin ' . ($j + 1) . ' es menor a su hora inicio', 422);
                }
                #Que las horas inicio después del primero no sea menor que la hora fin anterior
                if ($j != 0) {
                    $startNew = $horaryCustom['start' . ($j)];
                    $endPrevious = $horaryCustom['end' . ($j - 1)];

                    if ($startNew < $endPrevious) {
                        return $this->errorResponse('La hora inicio ' . ($j + 1) . ' es menor a su hora fin ' . ($j), 422);
                    }
                }
            }
            return $this->successResponse('OK');
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
