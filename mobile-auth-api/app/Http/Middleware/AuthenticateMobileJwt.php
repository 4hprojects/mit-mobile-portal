<?php

namespace App\Http\Middleware;

use App\Services\MobileJwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMobileJwt
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $user = app(MobileJwtService::class)->userFromToken($token);

        if (! $user || ! $user->isActive()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'unauthenticated',
                    'message' => 'A valid mobile session is required.',
                ],
            ], 401);
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
