<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClinicalHistoryRequest;
use App\Services\ClinicalHistoryService;
use App\Traits\HasResponse;
use Illuminate\Http\Request;

class ClinicalHistoryController extends Controller
{
    use HasResponse;
    /** @var  ClinicalHistoryService*/
    private $clinicalHistoryService;

    public function __construct(ClinicalHistoryService $clinicalHistoryService)
    {
        $this->clinicalHistoryService = $clinicalHistoryService;
    }

    public function index(Request $request)
    {
        $withPagination = $this->validatePagination($request->only('perPage', 'page'));
        return $this->clinicalHistoryService->listHistory($withPagination, $request);
    }

    public function listHistoryById($id, Request $request)
    {
        return $this->clinicalHistoryService->listHistoryById($id, $request);
    }

    public function create(ClinicalHistoryRequest $request)
    {
        return $this->clinicalHistoryService->create($request->validated());
    }

    public function update($id, Request $request)
    {
        return $this->clinicalHistoryService->update($id, $request->all());
    }

    public function markTooth($id, Request $request)
    {
        return $this->clinicalHistoryService->markTooth($id, $request->all());
    }

    public function markMultipleTeeth($id, Request $request)
    {
        return $this->clinicalHistoryService->markMultipleTeeth($id, $request->all());
    }
}
