<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserCredentialResource;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use App\Traits\HasResponse;

class VerificationController extends Controller
{
    use HasResponse;

    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);
        // Verificar si el hash coincide con el hash del correo electrónico del usuario
        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return $this->errorResponse('El enlace de verificación es inválido.',  404);
        }

        // Si ya está verificado, devolver un mensaje apropiado
        if ($user->hasVerifiedEmail()) {
            $user = User::activeForID($user->id)->first();
            $resourceUser = UserCredentialResource::make($user->load('role', 'rrhh'));
            return $this->successResponse('Su verificación ha sido exitosa, presione el botón "Iniciar" para redirigirle a su cuenta.',  $resourceUser);
        }
        // Marcar el correo como verificado y disparar el evento
        $user->markEmailAsVerified();
        event(new Verified($user));
        $user = User::activeForID($user->id)->first();
        $resourceUser = UserCredentialResource::make($user->load('role', 'rrhh'));
        return $this->successResponse('Su verificación ha sido exitosa, presione el botón "Iniciar" para redirigirle a su cuenta.', $resourceUser);
    }
}
