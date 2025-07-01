<?php

namespace App\Services;

use App\Enums\Status;
use App\Http\Resources\RRHHResource;
use App\Models\RRHH;
use App\Models\User;
use App\Traits\HasResponse;
use Illuminate\Support\Facades\DB;

class RRHHService
{
    use HasResponse;

    public function getRRHH($withPagination, $params)
    {
        try {
            $rrhh = RRHH::Filters()->active();
            $rrhh = $rrhh->orderBy('id', 'desc');
            $rrhh = !empty($withPagination)
                ? $rrhh->paginate($withPagination['perPage'], page: $withPagination['page'])
                : $rrhh->get();
            $paginationTotal = null;
            if (!empty($withPagination)) {
                $paginationTotal = $rrhh->total();
            }
            $rrhh = RRHHResource::collection($rrhh->load('charge'));
            return $this->successPaginationResponse('Lista exitosa', $paginationTotal, $rrhh);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getRRHHById($idrrhh)
    {
        try {
            $rrhh = RRHH::with('charge')
                ->activeForId($idrrhh)
                ->first();
            if (!$rrhh) {
                return $this->errorResponse('El registro no existe', 404);
            }
            return $this->successResponse('Registro encontrado', new RRHHResource($rrhh));
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function createRRHH($params)
    {
        DB::beginTransaction();
        try {
            $rrhh = RRHH::activeForDocument($params['n_document'])->first();
            if (isset($rrhh->id)) {
                return $this->errorResponse('El n_document ya se encuentra registrado', 409);
            }

            $rrhh = RRHH::create([
                'n_document'        => $params['n_document'],
                'name'              => $params['name'],
                'surname'           => $params['surname'] ?? '',
                'birth_date'        => $params['birth_date'] ?? null,
                'phone'             => $params['phone'] ?? null,
                'email'             => $params['email'],
                'photo'             => $params['photo'] ?? null,
                'idcharge'          => $params['idcharge'] ?? null,
            ]);
            $rrhh->fresh();

            DB::commit();
            return $this->successResponse('Registro creado satisfactoriamente', $rrhh, 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function updateRRHH($idrrhh, $params)
    {
        DB::beginTransaction();
        try {
            $rrhh = RRHH::activeForId($idrrhh)->first();
            if (!$rrhh) return $this->errorResponse('El registro no existe', 404);

            $oldDocument = $rrhh->n_document;

            // Actualizar RRHH
            $rrhh->update($params);

            // Verificamos si cambió n_document
            if (isset($params['n_document']) && $params['n_document'] !== $oldDocument) {
                // Actualizamos en User por idrrhh
                User::where('idrrhh', $idrrhh)->update([
                    'n_document' => $params['n_document']
                ]);
            }

            DB::commit();
            return $this->successResponse('registro actualizado satisfactoriamente', $rrhh->fresh());
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }


    public function deleteRRHH($idrrhh)
    {
        DB::beginTransaction();
        try {
            $rrhh = RRHH::activeForId($idrrhh)->first();
            if (!$rrhh) return $this->errorResponse('El registro no existe', 404);
            $rrhh->markAsDeleted();

            DB::commit();
            return $this->successResponse('Registro eliminado satisfactoriamente', $rrhh);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function uploadPhoto($idrrhh, $params)
    {
        DB::beginTransaction();
        try {
            // Validación
            $validate = $this->validateUploadPhoto($params);
            if (!$validate->original['status']) {
                return $this->errorResponse($validate->original['data']['message'], $validate->original['code']);
            }

            // Obtener el RRHH
            $rrhh = RRHH::activeForID($idrrhh)->first();
            if (!$rrhh) {
                return $this->errorResponse('No se encontró el usuario', 404);
            }

            // Eliminar foto anterior si existe
            if ($rrhh->photo) {
                $oldRoute = public_path($rrhh->photo);
                if (file_exists($oldRoute)) {
                    unlink($oldRoute);
                }
            }

            // Definir ruta para la nueva foto
            $rute = "rrhh/$idrrhh/photo/";
            if (!is_dir($rute)) {
                mkdir($rute, 0777, true);
            }

            // Generar nombre único para la foto
            $file_name = time() . '-' . uniqid() . '-' . str_replace(' ', '_', $params['photo']->getClientOriginalName());

            // Mover la foto
            $file = str_replace('\\', '/', $params['photo']->move($rute, $file_name));

            // Actualizar el modelo
            $rrhh->photo = $file;
            $rrhh->save();

            DB::commit();

            return $this->successResponse("Foto actualizada con éxito", $rrhh);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->errorResponse('Error al actualizar la foto', 500);
        }
    }


    private function validateUploadPhoto($params)
    {
        try {
            // Verifica si la foto está presente
            if (!isset($params['photo'])) {
                return $this->errorResponse('No se ha proporcionado ninguna foto', 400);
            }

            // Verifica si es un archivo válido
            if (!$params['photo']->isValid()) {
                return $this->errorResponse('Archivo de foto inválido', 400);
            }

            // Verifica si el archivo es una imagen
            if (!in_array($params['photo']->getClientMimeType(), ['image/jpeg', 'image/png', 'image/gif'])) {
                return $this->errorResponse('Tipo de archivo no permitido. Se requieren imágenes JPEG, PNG o GIF', 400);
            }

            // Verifica el tamaño del archivo (por ejemplo, no debe ser mayor a 2MB)
            if ($params['photo']->getSize() > 2 * 1024 * 1024) { // 2MB
                return $this->errorResponse('El tamaño del archivo excede el límite permitido de 2MB', 400);
            }

            return $this->successResponse('Ok');
        } catch (\Throwable $th) {
            // Captura excepciones y devuelve un mensaje claro
            return $this->errorResponse('Error al validar la foto', 500);
        }
    }
}
