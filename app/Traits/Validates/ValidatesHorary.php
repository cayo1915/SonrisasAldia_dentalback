<?php

namespace App\Traits\Validates;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

trait ValidatesHorary
{

    protected function validateTime(array $hourRange, $params)
    {
        try {
            if (empty($hourRange['start']) || empty($hourRange['end'])) {
                return $this->errorResponse('Rango horario inválido', 400);
            }

            if (!isset($params['wait_time_hour']) || !is_numeric($params['wait_time_hour'])) {
                return $this->errorResponse('Tiempo de espera inválido', 400);
            }

            $start = Carbon::parse($hourRange['start']);
            $end = Carbon::parse($hourRange['end']);

            // Total minutos disponibles entre start y end
            $timeAvailables = $end->diffInMinutes($start);

            if ($params['wait_time_hour'] > $timeAvailables) {
                return $this->errorResponse('El tiempo de espera no debe exceder el rango de su horario', 422);
            }

            return $this->successResponse('OK');
        } catch (\Throwable $th) {
            // Opcional: registrar el error antes de lanzar
            Log::error($th);
            throw $th;
        }
    }
}
