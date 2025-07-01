<?php

namespace App\Http\Middleware;

use App\Traits\HasResponse;
use Closure;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Http\Middleware\BaseMiddleware;

class Authenticate extends BaseMiddleware
{

    use HasResponse;

    public function handle($request, Closure $next)
    {

        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!!$user) {
                return $next($request);
            }
            return $this->errorResponse('No login: You are not authorized', 401);
        } catch (JWTException $e) {
            if ($e instanceof TokenInvalidException) {
                return $this->errorResponse('Token error: Token is Invalid', 401);
            } else if ($e instanceof TokenExpiredException) {
                return $this->errorResponse('Token error: Token is Expired', 401);
            } else {
                return $this->errorResponse('Token error: Authorization Token not found', 401);
            }
        }
    }
}
