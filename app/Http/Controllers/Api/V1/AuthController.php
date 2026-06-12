<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SsoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AuthController extends Controller
{
    public function login(Request $request, SsoService $sso): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            $response = $sso->login($credentials['email'], $credentials['password']);
            $claims = $sso->verifyUserToken((string) $response['token']);
            $user = $sso->mapLocalUser($claims);
        } catch (Throwable) {
            return response()->json([
                'status' => 'error',
                'message' => 'Login SSO gagal. Periksa email dan password.',
                'errors' => null,
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Login SSO berhasil.',
            'data' => [
                'token' => $response['token'],
                'token_type' => 'Bearer',
                'expires_in' => $response['expires_in'] ?? 3600,
                'profile' => [
                    'subject' => $user->sso_subject,
                    'name' => $user->name,
                    'email' => $user->email,
                    'nim' => $user->nim,
                    'local_role' => $user->role->name,
                ],
            ],
            'meta' => [
                'service_name' => config('iae.service_name'),
                'api_version' => config('iae.api_version'),
            ],
        ]);
    }
}
