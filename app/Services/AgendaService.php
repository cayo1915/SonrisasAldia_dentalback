<?php

namespace App\Services;

use App\Enums\Status;
use App\Http\Resources\AgendaResource;
use App\Models\Agenda;
use App\Models\AgendaDaysHours;
use App\Models\Horary;
use App\Traits\AgendaHelpers\ScheduleResetTrait;
use App\Traits\AgendaHelpers\AssignColorTrait;
use App\Traits\HasResponse;
use App\Traits\Validates\ValidatesAgenda;
use App\Traits\Validates\ValidatesReservationTrait;
use Illuminate\Support\Facades\DB;

class AgendaService
{
    use HasResponse;
    use AssignColorTrait;
    use ValidatesAgenda;
    use ScheduleResetTrait;
    use ValidatesReservationTrait;

    /** @var HoraryService */
    private $horaryService;

    public function __construct(HoraryService $horaryService)
    {
        $this->horaryService = $horaryService;
    }

    public function createAgenda($params)
    {
        DB::beginTransaction();
        try {
            // $doctorId           = $params['doctor_id'] ?? null;
            $doctorIds          = $params['doctor_ids'] ?? []; // Array de IDs
            $modalityIds        = $params['modalityIds'] ?? null;
            $durationHour       = $params['duration_hour'] ?? null;
            $waitTimeHour       = $params['wait_time_hour'] ?? null;
            $breakStart         = $params['break_start'] ?? null;
            $breakEnd           = $params['break_end'] ?? null;

            if (empty($doctorIds)) {
                return $this->errorResponse('Debe proporcionar al menos un doctor', 402);
            }
            foreach ($doctorIds as $doctorId) {
                $params['doctor_id'] = $doctorId;
                $validate = $this->verificationDateAvailability($params);
                if (!$validate->original['status']) {
                    return $validate;
                }
                $validate = $this->validateDoctor($doctorId);
                if (!$validate->original['status']) {
                    return $validate;
                }
            }

            // Verificar disponibilidad antes de seguir
            $validate = $this->verificationDateAvailability($params);
            if (!$validate->original['status']) {
                return $validate;
            }

            // Preparar datos de horarios
            $insertData = [];
            foreach ($params['days_array'] as $newDay) {
                $insertData[] = [
                    'idday'         => $newDay['day'],
                    'start_hour'    => $newDay['start'],
                    'end_hour'      => $newDay['end'],
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];
            }

            // Si es previsualización
            if (isset($params['preview']) && $params['preview']) {
                $previewAgenda = new Agenda();
                $previewAgenda->modality            = $modalityIds ?? null;
                $previewAgenda->name                = $params['name'];
                $previewAgenda->iddoctor            = $doctorId;
                $previewAgenda->duration_hour       = $durationHour;
                $previewAgenda->wait_time_hour      = $waitTimeHour;
                $previewAgenda->break_start         = $breakStart;
                $previewAgenda->break_end           = $breakEnd;
                $previewAgenda->days                = $insertData;

                DB::rollBack(); // no guardamos nada real
                $response  = $this->horaryService->automaticHorary(null, $params);
                if (!$response->original['status']) {
                    return $response;
                }
                $previewHorary = $response->original['data']['detail'];
                return $this->successResponse('Previsualización de agenda', $previewHorary);
            }
            foreach ($doctorIds as $doctorId) {
                // Selección de color
                $colorAgenda = $this->getNextAvailableColor();

                // Crear agenda real
                $agenda = new Agenda();
                $agenda->modality           = $modalityIds ?? null;
                $agenda->name               = $params['name'];
                $agenda->iddoctor           = $doctorId;
                $agenda->color_agenda       = $colorAgenda;
                $agenda->duration_hour      = $durationHour;
                $agenda->wait_time_hour     = $waitTimeHour;
                $agenda->break_start        = $breakStart;
                $agenda->break_end          = $breakEnd;
                $agenda->save();

                // Insertar horarios
                foreach ($insertData as &$day) {
                    $day['idagenda'] = $agenda->id;
                }
                unset($day);

                if (!empty($insertData)) {
                    AgendaDaysHours::insert($insertData);
                }
                $params['doctor_id'] = $doctorId;
                // Generar horarios automáticos
                $response = $this->horaryService->automaticHorary($agenda->id, $params);
                if (!$response->original['status']) {
                    return $response;
                }
            }
            DB::commit();
            return $this->successResponse('Agenda creada exitosamente', $agenda);
            // return $this->successResponse('Agendas creadas exitosamente para todos los doctores', $agenda, 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function editAgenda($id, $params) //editar dias, horas
    {

        try {
            DB::beginTransaction();

            #Verificar que la agenda sea de tipo disponibilidad
            $doctorId  = $params['doctor_id'] ?? null;

            $agenda = Agenda::where('id', $id)
                ->where('iddoctor', $doctorId)
                ->availability()
                ->active()
                ->first();

            if (!$agenda) return $this->errorResponse('Agenda no válida.', 404);

            $agendas = Agenda::active()
                ->whereNot('id', $id)
                ->availability()
                ->where('iddoctor', $agenda->iddoctor)
                ->get();

            if ($this->shouldRecreateAgendaSchedules($params, $agenda)) {
                $resetSchedules = $this->resetAgendaSchedules($agenda->id, $params);
                if (!$resetSchedules->original['status']) {
                    return $resetSchedules;
                }
                // Se debe eliminar y recrear los horarios
                // si estos datos cambian tenemos que eliminar y volver a crear horarios, por lo que tenemos que reagendar todas las reservas hacia adelante
                if ($params['preview'] === true) {
                    return $resetSchedules;
                }
                DB::commit();
                return $this->successResponse('Agenda editada con éxito', $agenda);
            }
            #Verificar si se han creado registros vigente con la agenda, estados 0 y 1
            $horaries  = Horary::where('idagenda', $id)
                ->active()
                ->get();
            #En caso que haya registros creados por la agenda hay tres caminos
            if ($horaries->isNotEmpty()) {

                $breakStart         = $params['break_start'] ?? null;
                $breakEnd           = $params['break_end'] ?? null;
                $duration = $params['duration_hour'] ?? null;
                $wait_time = $params['wait_time_hour'] ?? null;


                // Días nuevos que se recibirán (para agregar o actualizar)
                $daysArray = $params['days_array'];
                $daysArrayOriginal = $params['days_array'];
                $preview = $params['preview'];
                $daysEdit = [];
                foreach ($daysArray as $dayItem) {
                    $daysEdit[$dayItem['day']][] = [
                        'start' => $dayItem['start'],
                        'end'   => $dayItem['end']
                    ];
                }
                // 1. Obtener días guardados previamente en la base de datos para esta agenda
                $agendaDaysHours = AgendaDaysHours::where('idagenda', $agenda->id)->active();
                $daysAgenda = $agendaDaysHours->pluck('idday')->toArray();
                $daysObjectAgenda = $agendaDaysHours->get();

                // 2. Extraer los días del nuevo array enviado por el usuario
                $days = array_column($daysArray, 'day'); // ej: [1, 2, 3, 4, 5]

                $coincidences = array_keys(array_flip(array_intersect($daysAgenda, $days)));
                $differences = array_keys(array_flip(array_diff($days, $daysAgenda)));
                # Se encuentra coincidencias y diferencias, aumenta y/o reduce días
                if ((count($differences) > 0) && (count($coincidences) > 0)) {
                    #Verificar si se estan quitando días y añadiendo días
                    $subtract = array_keys(array_flip(array_diff($daysAgenda, $days)));
                    if ((count($subtract) > 0) && (count($differences) > 0)) {

                        #Verificar que los horarios no esten en reservas
                        //falta desarrollar esta validacion
                        $validateReservation = $this->validateExistsAgendaAndReservations($id, $params, $subtract);
                        if (!$validateReservation->original['status']) {
                            return $validateReservation;
                        }

                        #Validar que los días a ingresar no estan siendo ocupados en otros registros
                        $validate = $this->validateNewDays($agendas, $daysArrayOriginal);
                        if (!$validate->original['status']) {
                            return $validate;
                        }

                        $detail = [];
                        $daysInt = array_map('intval', $subtract); //ids que se van a quitar
                        foreach ($daysInt as $value) {
                            foreach ($daysObjectAgenda as $dayObjectAgenda) {
                                if ($value === $dayObjectAgenda['idday']) {
                                    $detail[] = [
                                        'day' => $value,
                                        'remove' => true,
                                        'start' => $dayObjectAgenda['start_hour'],
                                        'end' => $dayObjectAgenda['end_hour']
                                    ];
                                }
                            }
                        }
                        $daysInt = array_map('intval', $differences); //ids que se van a agregar
                        foreach ($daysInt as $value) {
                            foreach ($daysArray as $dayrray) {
                                if ($value === $dayrray['day']) {
                                    $detail[] = [
                                        'day' => $value,
                                        'remove' => false,
                                        'start' => $dayrray['start'],
                                        'end' => $dayrray['end']
                                    ];
                                }
                            }
                        }
                        $params['days_array'] = $detail;

                        if ($preview) {
                            //se genera solo con los diferentes
                            $response = $this->generateHorary($id, $params);
                            if (!$response->original['status']) {
                                return $response;
                            }
                            $horariesAdd = $response->original['data']['detail'];

                            //ahora generamos con las coincidencias
                            $responseModifyHours = $this->modifyAgendaHoursByDay($id, $coincidences, $params, $daysArrayOriginal);
                            // dd($responseModifyHours->original['data']['detail']['for_reschedule']);
                            if (!$responseModifyHours->original['status']) {
                                return $responseModifyHours;
                            }
                            $listCoincidences = $responseModifyHours->original['data']['detail']['general'];
                            $reservationReprogramation = $responseModifyHours->original['data']['detail']['for_reschedule'];
                            $reservationReprogramation2 = $validateReservation->original['data']['detail']['for_reschedule'];
                            $finalResult = [];

                            if (is_array($horariesAdd)) {
                                // Combinar el array $response con la colección convertida a array
                                $finalResult = array_merge($horariesAdd, $listCoincidences);
                            }
                            if ($reservationReprogramation2->isNotEmpty()) {
                                $dataPreview = [
                                    'schedules' => $finalResult,
                                    'for_reschedule' => $reservationReprogramation2
                                ];
                            } else {
                                $dataPreview = [
                                    'schedules' => $finalResult,
                                    'for_reschedule' => $reservationReprogramation
                                ];
                            }


                            return $this->successResponse("Vista previa cargada", $dataPreview);

                            #Confirmación de eliminado de registros
                        } else {
                            //generamos horarios;
                            $response = $this->generateHorary($id, $params);
                            if (!$response->original['status']) {
                                return $response;
                            }

                            $responseModifyHours = $this->modifyAgendaHoursByDay($id, $coincidences, $params, $daysArrayOriginal);
                            if (!$responseModifyHours->original['status']) {
                                return $responseModifyHours;
                            }
                            //agregamos la relacion de la agenda con el dia a agregarse
                            $insertData = [];
                            foreach ($params['days_array'] as $newDay) {
                                $dayRemove = $newDay['remove'];
                                if (!$dayRemove) {
                                    $dayId = $newDay['day'];
                                    $insertData[] = [
                                        'idagenda'      => $agenda->id,
                                        'idday'         => $dayId,
                                        'start_hour'    => $newDay['start'],
                                        'end_hour'      => $newDay['end'],
                                        'created_at'    => now(),
                                        'updated_at'    => now()
                                    ];
                                }
                            }
                            // Realizamos la inserción masiva si tenemos datos
                            if (!empty($insertData)) {
                                AgendaDaysHours::insert($insertData);
                            }

                            //eliminamos la relacion de la agenda con el dia
                            AgendaDaysHours::whereIn('idday', $subtract)
                                ->where('idagenda', $id)
                                ->active()
                                ->update(['status' => Status::DELETED->value]);

                            // si existe horarios a eliminar, procedemos
                            Horary::whereIn('idday', $subtract)
                                ->where('idagenda', $id)
                                ->active()
                                ->update(['status' => Status::DELETED->value]);
                            //actualizamos los nuevos valores en la agenda
                            Agenda::where('id', $id)->active()
                                ->update([
                                    'break_start'       => $breakStart,
                                    'break_end'         => $breakEnd,
                                    'duration_hour'     => $duration,
                                    'wait_time_hour'    => $wait_time,
                                    'name'              => $params['name'],
                                    'modality'          => $params['modalityIds']
                                ]);
                            DB::commit();
                            return $this->successResponse('Modificación de días exitosa', AgendaResource::collection(Agenda::where('id', $agenda->id)->get()->load('agendaDaysHours.day')));
                        }

                        #Los días a insertar contienen todos los días del registro y se adicionan más
                    } elseif ((count($subtract) == 0) && (count($differences) > 0) && (count($coincidences) > 0)) {
                        #Validar que los días a ingresar no estan siendo ocupados en otros registros
                        $validate = $this->validateNewDays($agendas, $daysArrayOriginal);
                        if (!$validate->original['status']) {
                            return $validate;
                        }

                        $detail = [];
                        $daysInt = array_map('intval', $differences); //ids que se van a agregar
                        foreach ($daysInt as $value) {
                            foreach ($daysArray as $dayrray) {
                                if ($value === $dayrray['day']) {
                                    $detail[] = [
                                        'day' => $value,
                                        'remove' => false,
                                        'start' => $dayrray['start'],
                                        'end' => $dayrray['end']
                                    ];
                                }
                            }
                        }

                        $params['days_array'] = $detail;
                        if ($preview) {
                            $response = $this->generateHorary($id, $params);
                            if (!$response->original['status']) {
                                return $response;
                            }
                            $horariesAdd = $response->original['data']['detail'];
                            $responseModifyHours = $this->modifyAgendaHoursByDay($id, $coincidences, $params, $daysArrayOriginal);
                            if (!$responseModifyHours->original['status']) {
                                return $responseModifyHours;
                            }
                            $listCoincidences = $responseModifyHours->original['data']['detail']['general'];
                            $reservationReprogramation = $responseModifyHours->original['data']['detail']['for_reschedule'];
                            $finalResult = [];

                            if (is_array($horariesAdd)) {
                                // Combinar el array $response con la colección convertida a array
                                $finalResult = array_merge($horariesAdd, $listCoincidences);
                            }

                            $dataPreview = [
                                'schedules' => $finalResult,
                                'for_reschedule' => $reservationReprogramation
                            ];

                            return $this->successResponse("Vista previa cargada", $dataPreview);
                        } else {
                            //generamos horarios;
                            $response = $this->generateHorary($id, $params);
                            if (!$response->original['status']) {
                                return $response;
                            }
                            $responseModifyHours = $this->modifyAgendaHoursByDay($id, $coincidences, $params, $daysArrayOriginal);
                            if (!$responseModifyHours->original['status']) {
                                return $responseModifyHours;
                            }
                            //agregamos la relacion de la agenda con el dia a agregarse
                            $insertData = [];
                            foreach ($params['days_array'] as $newDay) {
                                $dayRemove = $newDay['remove'];
                                if (!$dayRemove) {
                                    $dayId = $newDay['day'];
                                    $insertData[] = [
                                        'idagenda'      => $agenda->id,
                                        'idday'         => $dayId,
                                        'start_hour'    => $newDay['start'],
                                        'end_hour'      => $newDay['end'],
                                        'created_at'    => now(),
                                        'updated_at'    => now()
                                    ];
                                }
                            }
                            // Realizamos la inserción masiva si tenemos datos
                            if (!empty($insertData)) {
                                AgendaDaysHours::insert($insertData);
                            }

                            //actualizamos los nuevos valores en la agenda
                            Agenda::where('id', $id)->active()
                                ->update([
                                    'break_start'       => $breakStart,
                                    'break_end'         => $breakEnd,
                                    'duration_hour'     => $duration,
                                    'wait_time_hour'    => $wait_time,
                                    'name'              => $params['name'],
                                    'modality'          => $params['modalityIds']
                                ]);

                            DB::commit();
                            return $this->successResponse('Modificación de días exitosa', AgendaResource::collection(Agenda::where('id', $agenda->id)->get()->load('agendaDaysHours.day')));
                        }
                    }
                }
                # Se encuentra solo coincidencias, solo va a reducir
                elseif (count($coincidences) > 0) {
                    $subtract = array_keys(array_flip(array_diff($daysAgenda, $days)));
                    #Verificar que los horarios no esten en reservas
                    //falta desarrollar esta validacion
                    $validateReservation = $this->validateExistsAgendaAndReservations($id, $params, $subtract);
                    if (!$validateReservation->original['status']) {
                        return $validateReservation;
                    }
                    $detail = [];
                    $daysInt = array_map('intval', $subtract); //ids que se van a quitar
                    foreach ($daysInt as $value) {
                        foreach ($daysObjectAgenda as $dayObjectAgenda) {
                            if ($value === $dayObjectAgenda['idday']) {
                                $detail[] = [
                                    'day' => $value,
                                    'remove' => true,
                                    'start' => $dayObjectAgenda['start_hour'],
                                    'end' => $dayObjectAgenda['end_hour']
                                ];
                            }
                        }
                    }

                    if ($preview) {
                        $responseModifyHours = $this->modifyAgendaHoursByDay($id, $coincidences, $params, $daysArrayOriginal);
                        if (!$responseModifyHours->original['status']) {
                            return $responseModifyHours;
                        }
                        $listCoincidences = $responseModifyHours->original['data']['detail']['general'];
                        $reservationReprogramation = $responseModifyHours->original['data']['detail']['for_reschedule'];
                        $reservationReprogramation2 = $validateReservation->original['data']['detail']['for_reschedule'];

                        if ($reservationReprogramation2->isNotEmpty()) {
                            $dataPreview = [
                                'schedules' => $listCoincidences,
                                'for_reschedule' => $reservationReprogramation2
                            ];
                        } else {
                            $dataPreview = [
                                'schedules' => $listCoincidences,
                                'for_reschedule' => $reservationReprogramation
                            ];
                        }

                        return $this->successResponse("Vista previa cargada", $dataPreview);

                        // return $this->successResponse("Vista previa cargada", $listCoincidences);
                    } else {
                        //eliminamos la relacion de la agenda con el dia
                        $responseModifyHours = $this->modifyAgendaHoursByDay($id, $coincidences, $params, $daysArrayOriginal);
                        if (!$responseModifyHours->original['status']) {
                            return $responseModifyHours;
                        }
                        AgendaDaysHours::whereIn('idday', $subtract)
                            ->where('idagenda', $id)
                            ->active()
                            ->update(['status' => Status::DELETED->value]);

                        // si existe horarios a eliminar, procedemos
                        Horary::whereIn('idday', $subtract)
                            ->where('idagenda', $id)
                            ->active()
                            ->update(['status' => Status::DELETED->value]);


                        //actualizamos los nuevos valores en la agenda
                        Agenda::where('id', $id)->active()
                            ->update([
                                'break_start'       => $breakStart,
                                'break_end'         => $breakEnd,
                                'duration_hour'     => $duration,
                                'wait_time_hour'    => $wait_time,
                                'name'              => $params['name'],
                                'modality'          => $params['modalityIds']
                            ]);

                        DB::commit();
                        return $this->successResponse('Modificación de días exitosas', AgendaResource::collection(Agenda::where('id', $agenda->id)->get()->load('agendaDaysHours.day')));
                    }
                }

                # Se encuentran solo diferencias, solo va a aumentar o reducir todo lo ya creado
                elseif (count($differences) > 0) {
                    #Validar que los días a ingresar no estan siendo ocupados en otros registros
                    $validate = $this->validateNewDays($agendas, $daysArrayOriginal);
                    if (!$validate->original['status']) {
                        return $validate;
                    }

                    $differencesDelete = array_keys(array_flip(array_diff($daysAgenda, $days)));

                    #Verificar que los horarios no esten en reservas
                    //falta desarrollar esta validacion
                    $validateReservation = $this->validateExistsAgendaAndReservations($id, $params, $differencesDelete);
                    if (!$validateReservation->original['status']) {
                        return $validateReservation;
                    }

                    $detail = [];
                    $daysInt = array_map('intval', $differencesDelete);
                    foreach ($daysInt as $key => $value) { //dias a eliminarse
                        foreach ($daysObjectAgenda as $dayObjectAgenda) {
                            if ($value === $dayObjectAgenda['idday']) {
                                $detail[] = [
                                    'day' => $value,
                                    'remove' => true,
                                    'start' => $dayObjectAgenda['start_hour'],
                                    'end' => $dayObjectAgenda['end_hour']
                                ];
                            }
                        }
                    }
                    $daysInt = array_map('intval', $differences);
                    foreach ($daysInt as $key => $value) { //dias a agregarse
                        foreach ($daysArray as $dayrray) {
                            if ($value === $dayrray['day']) {
                                $detail[] = [
                                    'day' => $value,
                                    'remove' => false,
                                    'start' => $dayrray['start'],
                                    'end' => $dayrray['end']
                                ];
                            }
                        }
                    }

                    $params['days_array'] = $detail;
                    if ($preview) {
                        $response = $this->generateHorary($id, $params);
                        if (!$response->original['status']) {
                            return $response;
                        }
                        $responseModifyHours = $this->modifyAgendaHoursByDay($id, $coincidences, $params, $daysArrayOriginal);
                        if (!$responseModifyHours->original['status']) {
                            return $responseModifyHours;
                        }

                        $horariesAdd = $response->original['data']['detail'];
                        $reservationReprogramation2 = $validateReservation->original['data']['detail']['for_reschedule'];

                        $dataPreview = [
                            'schedules' => $horariesAdd,
                            'for_reschedule' => $reservationReprogramation2
                        ];

                        return $this->successResponse("Vista previa cargada", $dataPreview);
                    } else {
                        //generamos horarios;
                        $response = $this->generateHorary($id, $params);
                        if (!$response->original['status']) {
                            return $response;
                        }
                        //agregamos la relacion de la agenda con el dia a agregarse
                        $insertData = [];
                        foreach ($params['days_array'] as $newDay) {
                            $dayRemove = $newDay['remove'];
                            if (!$dayRemove) {
                                $dayId = $newDay['day'];
                                $insertData[] = [
                                    'idagenda'      => $agenda->id,
                                    'idday'         => $dayId,
                                    'start_hour'    => $newDay['start'],
                                    'end_hour'      => $newDay['end'],
                                    'created_at'    => now(),
                                    'updated_at'    => now()
                                ];
                            }
                        }
                        // Realizamos la inserción masiva si tenemos datos
                        if (!empty($insertData)) {
                            AgendaDaysHours::insert($insertData);
                        }

                        //eliminamos la relacion de la agenda con el dia
                        AgendaDaysHours::whereIn('idday', $differencesDelete)
                            ->where('idagenda', $id)
                            ->active()
                            ->update(['status' => Status::DELETED->value]);

                        // si existe horarios a eliminar, procedemos
                        Horary::whereIn('idday', $differencesDelete)
                            ->where('idagenda', $id)
                            ->active()
                            ->update(['status' => Status::DELETED->value]);

                        //actualizamos los nuevos valores en la agenda
                        Agenda::where('id', $id)->active()
                            ->update([
                                'break_start'       => $breakStart,
                                'break_end'         => $breakEnd,
                                'duration_hour'     => $duration,
                                'wait_time_hour'    => $wait_time,
                                'name'              => $params['name'],
                                'modality'          => $params['modalityIds']
                            ]);

                        DB::commit();
                        return $this->successResponse('Modificación de días exitosa', AgendaResource::collection(Agenda::where('id', $agenda->id)->get()->load('agendaDaysHours.day')));
                    }
                }
            }
            return $this->errorResponse('No existe horarios en está agenda', 404, $agenda);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}
