<?php

namespace App\Services;

use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Traits\HasResponse;
use Illuminate\Support\Facades\DB;

class RoleService
{
    use HasResponse;

    public function getRole($withPagination)
    {
        try {
            $role = Role::active();

            $role = !empty($withPagination)
                ? $role->paginate($withPagination['perPage'], page: $withPagination['page'])
                : $role->get();
            $paginationTotal = null;
            if (!empty($withPagination)) {
                $paginationTotal = $role->total();
            }
            $role = RoleResource::collection($role);
            return $this->successPaginationResponse('Lista exitosa', $paginationTotal, $role);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function createRole($params)
    {
        DB::beginTransaction();
        try {
            $role = Role::create([
                'name' => $params['name'],
            ]);
            $role->fresh();
            DB::commit();
            return $this->successResponse('Creado con éxito', $role, 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function updateRole($id, $params)
    {
        DB::beginTransaction();
        try {
            $role = Role::updateOrCreate(
                ['id' => $id],
                ['name' => $params['name']]
            );
            $role->fresh();
            DB::commit();
            return $this->successResponse('Rol editado con éxito', $role);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}
