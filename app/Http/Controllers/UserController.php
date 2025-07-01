<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangeUserRoleRequest;
use App\Http\Requests\UserRequest;
use App\Services\UserService;
use App\Traits\HasResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use HasResponse;
    /** @var UserService */
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(Request $request)
    {
        $withPagination = $this->validatePagination($request->only('perPage', 'page'));
        return $this->userService->getUser($withPagination, $request);
    }

    public function getAvailableDoctors(Request $request)
    {
        $withPagination = $this->validatePagination($request->only('perPage', 'page'));
        return $this->userService->getAvailableDoctors($request->all(), $withPagination);
    }

    public function getUserById($id, Request $request)
    {
        return $this->userService->getUserById($id, $request->all());
    }

    public function store(UserRequest $request)
    {
        return $this->userService->createUser($request->validated());
    }

    public function changeRole($id, ChangeUserRoleRequest $request)
    {
        return $this->userService->changeRole($id, $request->validated());
    }

    public function update($id, UserRequest $request)
    {
        return $this->userService->updateUser($id, $request->validated());
    }

    public function delete(int $iduser, Request $request)
    {
        return $this->userService->delete($iduser, $request->all());
    }

    public function generateUrlVerify($id)
    {
        return $this->userService->generateUrlVerify($id);
    }
}
