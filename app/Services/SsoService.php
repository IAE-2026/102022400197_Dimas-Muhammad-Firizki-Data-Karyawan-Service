<?php

namespace App\Services;

use App\Models\FederatedUser;
use App\Models\Role;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SsoService
{
    public function login(string $email, string $password): array
    {
        return Http::acceptJson()
            ->timeout(config('iae.http_timeout'))
            ->post($this->url('/api/v1/auth/token'), compact('email', 'password'))
            ->throw()
            ->json();
    }

    public function verifyUserToken(string $token): array
    {
        $jwks = Cache::remember('iae.sso.jwks', now()->addMinutes(10), function (): array {
            return Http::acceptJson()
                ->timeout(config('iae.http_timeout'))
                ->get($this->url('/api/v1/auth/jwks'))
                ->throw()
                ->json();
        });

        $claims = (array) JWT::decode($token, JWK::parseKeySet($jwks));

        if (($claims['iss'] ?? null) !== 'iae-central-mock' || ($claims['token_type'] ?? null) !== 'user') {
            throw new RuntimeException('JWT bukan token pengguna SSO IAE yang valid.');
        }

        return $claims;
    }

    public function mapLocalUser(array $claims): FederatedUser
    {
        $profile = (array) ($claims['profile'] ?? []);
        $email = (string) ($profile['email'] ?? $claims['sub'] ?? '');

        if ($email === '') {
            throw new RuntimeException('JWT SSO tidak memiliki identitas email.');
        }

        $role = Role::query()->firstOrCreate(
            ['name' => config('iae.default_role')],
            ['description' => 'Role lokal default untuk pengguna SSO']
        );

        $user = FederatedUser::query()->firstOrNew(['sso_subject' => (string) $claims['sub']]);
        $user->fill([
            'role_id' => $user->role_id ?: $role->id,
            'name' => (string) ($profile['name'] ?? $email),
            'email' => $email,
            'nim' => Arr::get($profile, 'nim'),
            'last_login_at' => now(),
        ])->save();

        return $user->load('role');
    }

    public function machineToken(): string
    {
        $apiKey = (string) config('iae.m2m_api_key');

        if ($apiKey === '') {
            throw new RuntimeException('IAE_M2M_API_KEY belum diisi pada environment.');
        }

        return Cache::remember('iae.sso.m2m_token', now()->addMinutes(50), function () use ($apiKey): string {
            $response = Http::acceptJson()
                ->timeout(config('iae.http_timeout'))
                ->post($this->url('/api/v1/auth/token'), ['api_key' => $apiKey])
                ->throw()
                ->json();

            return (string) ($response['token'] ?? throw new RuntimeException('Token M2M tidak ada pada respons SSO.'));
        });
    }

    private function url(string $path): string
    {
        return rtrim((string) config('iae.sso_base_url'), '/').$path;
    }
}
