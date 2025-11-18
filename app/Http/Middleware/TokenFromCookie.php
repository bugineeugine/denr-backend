<?php

namespace App\Http\Middleware;

use Closure;

class TokenFromCookie
{
    public function handle($request, Closure $next)
    {
        $token = $request->cookie('accessToken');
        logger()->info('AUTH accessToken', ['token' => $token]);
        if ($token && !$request->bearerToken()) {
            $request->headers->set('Authorization', 'Bearer '.$token);
        }

        return $next($request);
    }
}
