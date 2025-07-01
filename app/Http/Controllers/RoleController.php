<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Services\RoleService;
use App\Traits\HasResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    use HasResponse;
    /** @var  RoleService*/
    private $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    public function index(Request $request)
    {
        $withPagination = $this->validatePagination($request->only('perPage', 'page'));
        return $this->roleService->getRole($withPagination);
    }

    public function store(RoleRequest $request)
    {
        return $this->roleService->createRole($request->validated());
    }

    public function update($id, Request $request)
    {
        return $this->roleService->updateRole($id, $request->all());
    }

}
