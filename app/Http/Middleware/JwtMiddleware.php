<?php

namespace App\Http\Middleware;

use App\Traits\HasResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class JwtMiddleware
{
    use HasResponse;

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!!$user) {
                return $next($request);
            }
        } catch (JWTException $e) {
            if ($e instanceof TokenInvalidException) {
                return $this->errorResponse('Token error: Token es inválido', 401);
            } else if ($e instanceof TokenExpiredException) {
                return $this->errorResponse('Token error: Token expirado', 401);
            } else {
                return $this->errorResponse('Token error: Token de autorización no encontrado', 401);
            }
        }
        return $next($request);
    }
}
