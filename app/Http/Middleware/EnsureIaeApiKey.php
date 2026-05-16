<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIaeApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKey = (string) config('iae.api_key');
        $providedKey = (string) $request->header('X-IAE-KEY', '');

        if ($configuredKey === '' || ! hash_equals($configuredKey, $providedKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'API key tidak valid atau tidak dikirim.',
                'errors' => null,
            ], 401);
        }

        return $next($request);
    }
}
