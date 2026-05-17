<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateIntegrationSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredSecret = (string) Config::get('mobile_portal.integration_secret');
        $providedSecret = (string) $request->bearerToken();

        if ($configuredSecret === '' || ! hash_equals($configuredSecret, $providedSecret)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'invalid_integration_secret',
                    'message' => 'A valid server integration secret is required.',
                ],
            ], 401);
        }

        return $next($request);
    }
}
