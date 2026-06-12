<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RabbitMqPublisher
{
    public function publish(string $token, string $routingKey, array $payload): array
    {
        return Http::withToken($token)
            ->acceptJson()
            ->timeout(config('iae.http_timeout'))
            ->post(config('iae.publisher_url'), [
                'routing_key' => $routingKey,
                'message' => $payload,
            ])
            ->throw()
            ->json();
    }
}
