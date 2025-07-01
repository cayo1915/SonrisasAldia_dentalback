<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReservationRequest;
use App\Services\ReservationService;
use App\Traits\HasResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    use HasResponse;

    /** @var ReservationService */
    private $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        $this->middleware('auth:api');
        $this->reservationService = $reservationService;
    }

    public function index(Request $request)
    {
        $withPagination = $this->validatePagination($request->only('perPage', 'page'));
        return $this->reservationService->getReservations($withPagination, $request);
    }

    public function patientsAttended(Request $request)
    {
        return $this->reservationService->patientsAttended($request->all());
    }

    public function store(ReservationRequest $request): JsonResponse
    {
        return $this->reservationService->createReservation($request->validated());
    }

    public function canReschedule($id): JsonResponse
    {
        return $this->reservationService->canReschedule($id);
    }

    public function rescheduleReservation($id, ReservationRequest $request): JsonResponse
    {
        return $this->reservationService->rescheduleReservation($id, $request->validated());
    }

    public function updateAtrributes($id, Request $request)
    {
        return $this->reservationService->updateAtrributes($id, $request->all());
    }
}
