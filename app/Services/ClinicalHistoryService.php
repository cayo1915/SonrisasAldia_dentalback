<?php

namespace App\Services;

use App\Http\Resources\ClinicalHistoryResource;
use App\Models\ClinicalHistory;
use App\Models\ToothChartTooth;
use App\Models\ToothModelTooth;
use App\Traits\HasResponse;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;

class ClinicalHistoryService
{
    use HasResponse;
    /** @var UserService */
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function listHistory($withPagination, $request)
    {
        try {
            $clinicalHistory = ClinicalHistory::filters($request)->active();
            $clinicalHistory = $clinicalHistory->orderBy('id', 'desc');
            $clinicalHistory = !empty($withPagination)
                ? $clinicalHistory->paginate($withPagination['perPage'], page: $withPagination['page'])
                : $clinicalHistory->get();
            $paginationTotal = null;
            if (!empty($withPagination)) {
                $paginationTotal = $clinicalHistory->total();
            }
            $clinicalHistory = ClinicalHistoryResource::collection($clinicalHistory->load('teeth', 'doctor.rrhh', 'patient.rrhh'));
            return $this->successPaginationResponse('Lista exitosa', $paginationTotal, $clinicalHistory);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function listHistoryById($id, $request)
    {
        try {
            $chart = ClinicalHistory::filters($request)
                ->where('id', $id)
                ->active()
                ->first();

            if (!$chart) {
                return $this->errorResponse('No existe un historial clínico', 404);
            }
            $chart->load('teeth', 'doctor.rrhh', 'patient.rrhh');
            return $this->successResponse('Lectura exitosa',  ClinicalHistoryResource::make($chart));
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener historial clinico: ' . $e->getMessage(), 500);
        }
    }

    public function create($request)
    {
        try {
            $toothModelId =  $request['tooth_model_id'] ?? 1;
            $existing = ClinicalHistory::where('doctor_id', $request['doctor_id'])
                ->where('patient_id', $request['patient_id'])
                ->where('reservation_id', $request['reservation_id'])
                ->active()
                ->first();

            if ($existing) {
                return $this->errorResponse('Ya existe un historial clínico para esta reserva, paciente y doctor.', 409);
            }
            $chart = ClinicalHistory::create([
                'doctor_id' => $request['doctor_id'],
                'patient_id' => $request['patient_id'],
                'reservation_id' => $request['reservation_id'],
                'tooth_model_id' => $toothModelId,
            ]);
            $modelTeeth = ToothModelTooth::where('tooth_model_id', $toothModelId)
                ->active()->get();

            foreach ($modelTeeth as $tooth) {
                ToothChartTooth::create([
                    'clinical_history_id' => $chart->id,
                    'tooth_number' => $tooth->tooth_number,
                    'is_checked' => false,
                    'observation' => null,
                    'quadrant' => $tooth->quadrant,
                ]);
            }
            return $this->successResponse('Historial clinico creado con éxito',  $chart->load('teeth'), 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al crear historial clinico: ' . $e->getMessage(), 500);
        }
    }

    public function update($id, array $request)
    {
        try {

            $history = ClinicalHistory::activeForID($id)->first();

            if (!$history) {
                return $this->errorResponse('No existe un historial clínico', 404);
            }

            $history->update([
                'register_date'     => $request['register_date'] ?? $history->register_date,
                'history_number'    => $request['history_number'] ?? $history->history_number,
                'document_number'   => $request['document_number'] ?? $history->document_number,
                'medical_condition' => $request['medical_condition'] ?? $history->medical_condition,
                'medications'       => $request['medications'] ?? $history->medications,
                'allergies'         => $request['allergies'] ?? $history->allergies,
                'observation'       => $request['observation'] ?? $history->observation,
            ]);

            $userData = array_intersect_key($request, array_flip([
                'n_document',
                'name',
                'surname',
                'birth_date',
                'phone'
            ]));
            // Actualizar datos del paciente
            $user = $this->userService->updateUser($history->patient_id, $userData);

            if (!$user->original['status']) {
                return $this->errorResponse('Error al actualizar datos del paciente', 422);
            }

            return $this->successResponse('Historial clínico actualizado con éxito', $history);
        } catch (QueryException $e) {
            return $this->errorResponse('Error en la base de datos: ' . $e->getMessage(), 500);
        } catch (\Throwable $e) {
            return $this->errorResponse('Ocurrió un error inesperado: ' . $e->getMessage(), 500);
        }
    }

    public function markTooth($id, $request)
    {
        try {

            $validator = Validator::make($request, [
                'tooth_number' => 'required|integer',
                'is_checked' => 'required|boolean',
                'observation' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }
            $tooth = ToothChartTooth::activeForID($id)
                ->where('tooth_number', $request['tooth_number'])
                ->first();

            if (!$tooth) {
                // Puedes lanzar una excepción, devolver un error o manejarlo como quieras
                return $this->errorResponse("No se encontró el diente con el número {$request['tooth_number']} para el chart {$id}", 404);
            }
            $tooth->is_checked = $request['is_checked'];
            $tooth->observation = $request['observation'] ?? null;
            $tooth->save();

            return $this->successResponse('Estado del diente actualizado con éxito',  $tooth);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al actualizar diente: ' . $e->getMessage(), 500);
        }
    }

    public function markMultipleTeeth($id, $request)
    {
        try {
            // Validación del array de dientes
            $validator = Validator::make($request, [
                'teeth' => 'required|array|min:1',
                'teeth.*.tooth_number' => 'required|integer',
                'teeth.*.is_checked' => 'required|boolean',
                'teeth.*.observation' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }

            $updatedTeeth = [];

            $history = ClinicalHistory::activeForID($id)->first();

            if (!$history) {
                return $this->errorResponse('No existe un historial clínico', 404);
            }

            foreach ($request['teeth'] as $toothData) {
                $tooth = ToothChartTooth::where('clinical_history_id', $history->id)
                    ->where('tooth_number', $toothData['tooth_number'])
                    ->first();

                if (!$tooth) {
                    // Si un diente no se encuentra, puedes continuar o lanzar error
                    return $this->errorResponse("No se encontró el diente con el número {$toothData['tooth_number']}", 404);
                }

                $tooth->is_checked = $toothData['is_checked'];
                $tooth->observation = $toothData['observation'] ?? null;
                $tooth->save();

                $updatedTeeth[] = $tooth;
            }

            return $this->successResponse('Dientes actualizados correctamente', $updatedTeeth);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al actualizar los dientes: ' . $e->getMessage(), 500);
        }
    }

}
