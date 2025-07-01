<?php

namespace App\Services;

use App\Models\Charge;
use App\Traits\HasResponse;
use Illuminate\Support\Facades\DB;

class ChargeService
{
    use HasResponse;

    public function get($withPagination, $params)
    {
        try {
            $charge = Charge::chargeFilters()->active()->orderBy('id', 'desc');

            $charge = !empty($withPagination)
                ? $charge->paginate($withPagination['perPage'], page: $withPagination['page'])
                : $charge->get();
            $paginationTotal = null;
            if (!empty($withPagination)) {
                $paginationTotal = $charge->total();
            }
            return $this->successPaginationResponse('Lista exitosa', $paginationTotal, $charge);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function create($params)
    {
        DB::beginTransaction();
        try {
            #Validar duplicidad de nombre
            $exits = Charge::where('name', $params['name'])->active()->first();
            if ($exits) return $this->errorResponse('El cargo ya se encuentra registrado', 409);

            $charge = Charge::create([
                'name'        => $params['name'],
                'description' => $params['description'] ?? '',
            ]);

            DB::commit();
            return $this->successResponse('Cargo creado satisfactoriamente', $charge, 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function update($id, $params)
    {
        DB::beginTransaction();
        try {
            #Validar existencia del registro
            $charge = Charge::activeForId($id)->first();
            if (!$charge) return $this->errorResponse('El cargo seleccionado no es válido.', 404);

            #Validar duplicidad de nombre
            $exits = Charge::where('id', '!=', $id)->where('name', $params['name'])->active()->first();
            if ($exits) return $this->errorResponse('El cargo ya se encuentra registrado', 409);

            $charge->update($params);

            DB::commit();
            return $this->successResponse('Cargo editado satisfactoriamente', $charge);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function delete($id)
    {
        DB::beginTransaction();
        try {
            // Obtener el cargo activo usando el scope
            $charge = Charge::activeForId($id)->first();

            if (!$charge) {
                return $this->errorResponse('El cargo seleccionado no es válido.', 404);
            }

            // Usar el método del modelo para marcar como eliminado
            $charge->markAsDeleted();

            DB::commit();
            return $this->successResponse('Cargo eliminado satisfactoriamente', $charge);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}
