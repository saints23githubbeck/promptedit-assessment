<?php

namespace App\Services\Pixelforge;

use App\Enums\GenerationStatus;
use App\Models\CreditLedgerEntry;
use App\Models\Generation;
use App\Models\PixelforgeEvent;
use App\Services\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class WebhookProcessor
{
    public function verify(string $rawBody, ?string $header): bool
    {
        $secret = (string) config('services.pixelforge.webhook_secret');

        if ($header === null || $header === '' || $secret === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            $parts[$key] = $value;
        }

        $timestamp = filter_var($parts['t'] ?? null, FILTER_VALIDATE_INT);
        $signature = $parts['v1'] ?? '';
        $tolerance = max(0, (int) config('services.pixelforge.webhook_tolerance', 300));

        if (!$timestamp || $signature === '' || abs(time() - $timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);

        return hash_equals($expected, $signature);
    }

    public function process(array $event): void
    {
        if (($event['object'] ?? null) !== 'event' || !is_string($event['id'] ?? null) || $event['id'] === '') {
            throw new InvalidArgumentException('Malformed Pixelforge event.');
        }

        DB::transaction(function () use ($event): void {
            $object = $event['data']['object'] ?? [];
            $providerId = is_array($object) ? ($object['id'] ?? null) : null;

            PixelforgeEvent::query()->insertOrIgnore([
                'event_id' => $event['id'],
                'event_type' => $event['type'] ?? 'unknown',
                'generation_id' => $providerId,
                'payload' => json_encode($event, JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $stored = PixelforgeEvent::query()
                ->where('event_id', $event['id'])
                ->lockForUpdate()
                ->first();

            if (!$stored || $stored->processed_at) {
                return;
            }

            $localId = $object['metadata']['local_generation_id'] ?? null;
            $generation = Generation::query()
                ->where('provider_id', $providerId ?? '')
                ->when($localId, fn ($query) => $query->orWhere('id', $localId))
                ->lockForUpdate()
                ->first();

            // The local metadata ID lets us associate a webhook even if it beats the
            // create response. Link the provider ID and process it immediately.
            if (!$generation) {
                return;
            }

            if (!$generation->provider_id && is_string($providerId) && $providerId !== '') {
                $generation->provider_id = $providerId;
            }

            $rates = $object['rates'] ?? $generation->rates;
            if (is_array($rates) && $rates !== []) {
                $generation->rates = $rates;
            }

            $incoming = GenerationStatus::tryFrom((string) ($object['status'] ?? ''));
            if ($incoming && !$generation->status->isFinal()) {
                $shouldAdvance = in_array($incoming, [GenerationStatus::Succeeded, GenerationStatus::Failed], true)
                    || ($incoming === GenerationStatus::Processing && $generation->status === GenerationStatus::Queued);

                if ($shouldAdvance) {
                    $generation->status = $incoming;
                    $generation->image_url = $object['image_url'] ?? $generation->image_url;
                    $generation->image_url_expires_at = $object['image_url_expires_at'] ?? $generation->image_url_expires_at;
                    $generation->usage_actual = $object['usage'] ?? null;
                    $generation->provider_error_code = $object['error']['code'] ?? null;
                    $generation->provider_error_message = $object['error']['message'] ?? null;
                    $generation->provider_completed_at = $object['completed_at'] ?? null;

                    if ($incoming === GenerationStatus::Succeeded) {
                        if (!is_array($object['usage'] ?? null) || !is_array($generation->rates) || !$generation->rates) {
                            throw new InvalidArgumentException('A succeeded Pixelforge generation must include usage and its rate snapshot.');
                        }

                        $providerCost = app(PricingService::class)->actualCost($object['usage'], $generation->rates);
                        $customerCost = Money::withMarkup($providerCost);

                        $generation->actual_provider_cost_units = $providerCost;
                        $generation->actual_customer_cost_units = $customerCost;

                        CreditLedgerEntry::query()->firstOrCreate(
                            ['generation_id' => $generation->id, 'type' => 'generation_charge'],
                            [
                                'user_id' => $generation->user_id,
                                'amount_units' => -$customerCost,
                                'description' => 'Image generation ' . ($generation->provider_id ?? $providerId),
                            ],
                        );
                    }
                }
            }

            $generation->save();
            $stored->update(['processed_at' => now()]);
        }, 3);
    }

    public function replayPending(string $providerId): void
    {
        $pending = PixelforgeEvent::query()
            ->where('generation_id', $providerId)
            ->whereNull('processed_at')
            ->orderBy('id')
            ->get();

        foreach ($pending as $record) {
            $this->process($record->payload);
        }
    }
}
