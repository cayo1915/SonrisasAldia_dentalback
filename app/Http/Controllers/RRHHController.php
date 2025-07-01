<?php

namespace App\Http\Controllers;

use App\Http\Requests\RRHHRequest;
use App\Services\RRHHService;
use App\Traits\HasResponse;
use Illuminate\Http\Request;

class RRHHController extends Controller
{
    use HasResponse;
    /** @var RRHHService */
    private $rrhhService;

    public function __construct(RRHHService $rrhhService)
    {
        $this->rrhhService = $rrhhService;
    }

    public function index(Request $request)
    {
        $withPagination = $this->validatePagination($request->only('perPage', 'page'));
        return $this->rrhhService->getRRHH($withPagination, $request);
    }

    public function getRRHHById($idrrhh)
    {
        return $this->rrhhService->getRRHHById($idrrhh);
    }

    public function store(RRHHRequest $request)
    {
        return $this->rrhhService->createRRHH($request->validated());
    }

    public function update(int $iduser, RRHHRequest $request)
    {
        return $this->rrhhService->updateRRHH($iduser, $request->validated());
    }

    public function delete(int $iduser)
    {
        return $this->rrhhService->deleteRRHH($iduser);
    }

    public function uploadPhoto(int $iduser, Request $request)
    {
        return $this->rrhhService->uploadPhoto($iduser, $request);
    }
}
