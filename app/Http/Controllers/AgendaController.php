<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\AgendaRequest;
use App\Http\Requests\AgendaUpdateRequest;
use App\Services\AgendaService;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    /** @var AgendaService */
    private $agendaService;

    public function __construct(AgendaService $agendaService)
    {
        $this->middleware('auth:api');
        $this->agendaService = $agendaService;
    }

    public function store(AgendaRequest $request)
    {
        return $this->agendaService->createAgenda($request->validated());
    }

    public function edit($id, AgendaUpdateRequest $request)
    {
        return $this->agendaService->editAgenda($id, $request->validated());
    }
}
