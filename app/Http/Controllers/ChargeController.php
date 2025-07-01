<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChargeRequest;
use App\Services\ChargeService;
use App\Traits\HasResponse;
use Illuminate\Http\Request;

class ChargeController extends Controller
{
    use HasResponse;
    /** @var ChargeService */
    private $chargeService;

    public function __construct(ChargeService $chargeService)
    {
        $this->chargeService = $chargeService;
    }

    public function index(Request $request)
    {
        $withPagination = $this->validatePagination($request->only('perPage', 'page'));
        return $this->chargeService->get($withPagination, $request);
    }

    public function store(ChargeRequest $request)
    {
        return $this->chargeService->create($request->validated());
    }

    public function update($id, ChargeRequest $request)
    {
        return $this->chargeService->update($id, $request->validated());
    }

    public function delete($id)
    {
        return $this->chargeService->delete($id);
    }
}
