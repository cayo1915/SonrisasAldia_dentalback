<?php

namespace App\Traits\AgendaHelpers;

use App\Enums\Status;
use App\Models\Day;
use App\Models\Horary;
use App\Traits\Validates\ValidatesHorary;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait HoraryEditorTrait
{
    use ValidatesHorary;

    protected function generateHorary($idagenda, $params)
    {
        DB::beginTransaction();
        try {
            $breakStatus        = $params['break_status'] ?? false;
            $breakStart         = null;
            $breakEnd           = null;
            #Verificar si se manda o no break
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

            $duration = $params['duration_hour'];
            $wait_time = $params['wait_time_hour'];
            $daysArray = $params['days_array'];

            $arrayHorary = [];

            foreach ($daysArray as $dayEntry) {
                $dayId = $dayEntry['day'];
                $dayRemove = $dayEntry['remove'] ?? false;
                if (!$dayRemove) {
                    $dayName = Day::where('id', $dayId)->first()->name;
                    $startRegister = new Carbon($dayEntry['start']);
                    $endRegister = new Carbon($dayEntry['end']);
                    $validateTime = $this->validateTime($dayEntry, $params);
                    if (!$validateTime->original['status']) {
                        return $validateTime;
                    }

                    $start = 0;
                    $j = 0;
                    $break = false;
                    $breakStartHour = false;

                    while (!is_null($start)) {
                        if ($j == 0) {
                            $startHour = $startRegister->copy();
                            $endHour = $startRegister->copy()->addMinutes($duration);
                        }
                        /* Apartir de la segunda iteración empieza la suma */
                        if ($j != 0) {
                            if ($break && !$breakStartHour) {
                                $startHour = $endHour;
                                $endHour = $startHour->copy()->addMinutes($duration);
                                $breakStartHour = true;
                            } else {
                                $startHour = $endHour->copy()->addMinute($wait_time);
                                $endHour = $startHour->copy()->addMinutes($duration);
                            }
                        }

                        if ($breakStatus === true) {
                            if ($endHour > $breakStart && !$break) {
                                $endHour = $breakEnd->copy();
                                $break = true;
                            } else {
                                $arrayHorary[] = [
                                    'idday' => $dayId,
                                    'nameDay' => $dayName,
                                    'start' => $startHour->toTimeString(),
                                    'end' => $endHour->toTimeString(),
                                    'duration' => $duration,
                                    'wait_time' => $wait_time,
                                    'idagenda' => $idagenda,
                                    'status' => Status::ACTIVE->value
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
                                'status' => Status::ACTIVE->value
                            ];
                        }

                        if ($endRegister < $endHour->copy()->addMinutes($duration)) {
                            $start = null;
                            break;
                        }
                        $j++;
                    }
                }
            }

            if ($params['preview'] === true) {
                return $this->successResponse('No puede crear horarios en modo previsualización', $arrayHorary);
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
                    'status'            =>  $row['status']
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
}
