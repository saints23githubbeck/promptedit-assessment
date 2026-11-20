<?php

namespace App\Console;

use App\Models\Generation;
use App\Services\Pixelforge\Client;
use App\Services\Pixelforge\WebhookProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

class PollPendingGenerations extends Command
{
    protected $signature = 'pixelforge:poll-pending';

    protected $description = 'Reconcile uncertain submissions and poll pending generations.';

    public function handle(Client $client, WebhookProcessor $processor): int
    {
        // Retry uncertain POSTs with the original idempotency key. If Pixelforge
        // accepted the first request, it returns the original generation.
        Generation::query()
            ->where('status', 'queued')
            ->whereNull('provider_id')
            ->where('submission_attempts', '<', 5)
            ->orderBy('id')
            ->chunkById(25, function ($generations) use ($client, $processor): void {
                foreach ($generations as $generation) {
                    try {
                        $generation->increment('submission_attempts');

                        $providerGeneration = $client->create([
                            'prompt' => $generation->prompt,
                            'size' => $generation->size,
                            'quality' => $generation->quality,
                            'reference_images' => $generation->reference_images ?? [],
                            'metadata' => ['local_generation_id' => (string) $generation->id],
                        ], $generation->idempotency_key);

                        $generation->refresh();
                        $generation->update([
                            'provider_id' => $providerGeneration['id'],
                            'rates' => $providerGeneration['rates'] ?? $generation->rates,
                            'provider_created_at' => $providerGeneration['created_at'] ?? now(),
                            'provider_error_code' => null,
                            'provider_error_message' => null,
                        ]);

                        $processor->replayPending($providerGeneration['id']);
                    } catch (Throwable $exception) {
                        report($exception);
                    }
                }
            });

        Generation::query()
            ->whereIn('status', ['queued', 'processing'])
            ->whereNotNull('provider_id')
            ->orderBy('id')
            ->chunkById(50, function ($generations) use ($client, $processor): void {
                foreach ($generations as $generation) {
                    try {
                        $providerGeneration = $client->retrieve($generation->provider_id);

                        // Reuse the same transactional state-transition path as webhooks.
                        $processor->process([
                            'id' => 'poll_' . Str::uuid(),
                            'object' => 'event',
                            'type' => 'generation.' . $providerGeneration['status'],
                            'created_at' => now()->toIso8601String(),
                            'data' => ['object' => $providerGeneration],
                        ]);
                    } catch (Throwable $exception) {
                        report($exception);
                    }
                }
            });

        return self::SUCCESS;
    }
}
