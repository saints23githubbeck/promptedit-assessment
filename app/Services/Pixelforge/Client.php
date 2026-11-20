<?php

namespace App\Services\Pixelforge;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class Client
{
    private function baseUrl(): string
    {
        $baseUrl = trim((string) config('services.pixelforge.base_url'));

        if ($baseUrl === '' || !filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('PIXELFORGE_BASE_URL must be a valid absolute URL.');
        }

        return rtrim($baseUrl, '/') . '/';
    }

    private function request(): PendingRequest
    {
        $apiKey = trim((string) config('services.pixelforge.api_key'));

        if ($apiKey === '') {
            throw new RuntimeException('PIXELFORGE_API_KEY is not configured.');
        }

        return Http::baseUrl($this->baseUrl())
            ->withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout((int) config('services.pixelforge.timeout', 15));
    }

    /** Pricing is public and deliberately does not send an API key. */
    public function pricing(): array
    {
        $response = Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(10)
            ->get('pricing')
            ->throw();

        $pricing = $response->json();

        if (!is_array($pricing) || !isset($pricing['rates'], $pricing['tokens'])) {
            throw new RuntimeException('Pixelforge returned an invalid pricing response.');
        }

        return $pricing;
    }

    public function create(array $payload, string $idempotencyKey): array
    {
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $response = $this->request()
                    ->withHeaders(['Idempotency-Key' => $idempotencyKey])
                    ->post('generations', $payload);

                if ($response->successful()) {
                    $generation = $response->json();

                    if (!is_array($generation) || empty($generation['id'])) {
                        throw new RuntimeException('Pixelforge returned an invalid generation response.');
                    }

                    return $generation;
                }

                $code = $response->json('error.code');
                $retryable = in_array($code, [
                    'rate_limit_exceeded',
                    'concurrency_limit_exceeded',
                    'overloaded',
                ], true);
                $retryAfter = max(1, (int) ($response->header('Retry-After') ?? 1));

                if (!$retryable || $attempt === 3 || $retryAfter > 5) {
                    $response->throw();
                }

                sleep($retryAfter);
            } catch (ConnectionException $exception) {
                if ($attempt === 3) {
                    throw $exception;
                }

                usleep(250_000 * $attempt);
            }
        }

        throw new RuntimeException('Pixelforge create retry loop ended unexpectedly.');
    }

    public function retrieve(string $id): array
    {
        return $this->request()->get('generations/' . rawurlencode($id))->throw()->json();
    }

    public function usage(string $start, string $end): array
    {
        return $this->request()
            ->get('usage', ['start_date' => $start, 'end_date' => $end])
            ->throw()
            ->json();
    }
}
