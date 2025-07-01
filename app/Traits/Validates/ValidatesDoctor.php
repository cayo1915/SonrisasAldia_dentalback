<?php

namespace App\Traits\Validates;

use App\Models\User;

trait ValidatesDoctor
{
    protected function validateDoctor(int $doctorId)
    {
        if (!$doctorId || !is_numeric($doctorId)) {
            return $this->errorResponse('ID de doctor inválido', 400);
        }

        $doctor = User::where('idrole', 2) // rol Médico
            ->activeForID($doctorId)
            ->first();

        if (!$doctor) {
            return $this->errorResponse('El doctor no se encuentra registrado', 404);
        }

        return $this->successResponse('Doctor válido', $doctor);
    }
}
