<?php

namespace App\Http\Middleware;

use App\Services\SsoService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthenticateFederatedUser
{
    public function __construct(private readonly SsoService $sso) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return $this->unauthorized('Bearer token SSO wajib dikirim.');
        }

        try {
            $claims = $this->sso->verifyUserToken($token);
            $user = $this->sso->mapLocalUser($claims);
        } catch (Throwable) {
            return $this->unauthorized('Bearer token SSO tidak valid atau kedaluwarsa.');
        }

        $request->attributes->set('sso_claims', $claims);
        $request->attributes->set('federated_user', $user);

        return $next($request);
    }

    private function unauthorized(string $message): Response
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => null,
        ], 401);
    }
}
