<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Traits\HasResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class PasswordResetController extends Controller
{
    use HasResponse;
    public function reset(Request $request)
    {
        // Validar que el código esté presente en la solicitud
        $validator = Validator::make($request->all(), [
            'n_document' => 'required|string',
            'code' => 'required|digits:6',
        ]);

        // Si la validación falla, devuelve una respuesta con los errores y código 400 (Bad Request)
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        // Obtener el código guardado en cache (puedes cambiar a base de datos si lo prefieres)
        $storedCode = Cache::get("password_reset_code_{$request->n_document}");
        // Verificar si el código existe y si coincide con el que el usuario envió
        if (!$storedCode) {
            return $this->errorResponse('El código ha expirado o no se ha encontrado.', 404);
        }
        if ($request->code != $storedCode) {
            return $this->errorResponse('El código ingresado es incorrecto.', 400);
        }

        // Si el código es correcto, encontrar al usuario
        $user = User::activeForDocument($request->n_document)->first();

        // El código es válido, puedes permitir el restablecimiento de la contraseña
        return $this->successResponse('Código verificado. Listo para cambiar la contraseña.', $user);
    }
}
