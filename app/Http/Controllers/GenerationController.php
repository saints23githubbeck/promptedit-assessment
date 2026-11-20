<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGenerationRequest;
use App\Models\Generation;
use App\Services\Money;
use App\Services\Pixelforge\Client;
use App\Services\Pixelforge\PricingService;
use App\Services\Pixelforge\WebhookProcessor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GenerationController extends Controller
{
    public function index(Request $request): Response
    {
        $generations = $request->user()
            ->generations()
            ->latest()
            ->paginate(12)
            ->through(fn (Generation $generation) => $this->resource($generation));

        return Inertia::render('History/Index', ['generations' => $generations]);
    }

    public function create(Client $client): Response
    {
        return Inertia::render('Generate/Create', [
            'pricing' => $client->pricing(),
            'defaults' => [
                'size' => '1024x1024',
                'quality' => 'medium',
            ],
        ]);
    }

    public function estimate(
        StoreGenerationRequest $request,
        Client $client,
        PricingService $pricing,
    ): JsonResponse {
        $data = $request->validated();
        $quote = $pricing->estimate(
            $data['prompt'],
            $data['size'],
            $data['quality'],
            $data['reference_images'] ?? [],
            $client->pricing(),
        );

        return response()->json([
            'estimated_cost' => Money::unitsToDecimal($quote['customer_cost_units']),
            'estimated_provider_cost' => Money::unitsToDecimal($quote['provider_cost_units']),
            'usage_estimate' => $quote['usage_estimate'],
        ]);
    }

    public function store(
        StoreGenerationRequest $request,
        Client $client,
        PricingService $pricing,
    ): RedirectResponse {
        $data = $request->validated();
        $referenceImages = $data['reference_images'] ?? [];
        $pricingResponse = $client->pricing();
        $rates = $pricingResponse['rates'];
        $quote = $pricing->estimate(
            $data['prompt'],
            $data['size'],
            $data['quality'],
            $referenceImages,
            $pricingResponse,
        );
        $idempotencyKey = (string) Str::uuid();

        $generation = DB::transaction(fn () => Generation::create([
            'user_id' => $request->user()->id,
            'idempotency_key' => $idempotencyKey,
            'prompt' => $data['prompt'],
            'size' => $data['size'],
            'quality' => $data['quality'],
            'reference_images' => $referenceImages,
            'status' => 'queued',
            'rates' => $rates,
            'usage_estimate' => $quote['usage_estimate'],
            'estimated_provider_cost_units' => $quote['provider_cost_units'],
            'estimated_customer_cost_units' => $quote['customer_cost_units'],
        ]));

        try {
            $providerGeneration = $client->create([
                'prompt' => $data['prompt'],
                'size' => $data['size'],
                'quality' => $data['quality'],
                'reference_images' => $referenceImages,
                'metadata' => ['local_generation_id' => (string) $generation->id],
            ], $idempotencyKey);

            $generation->update([
                'provider_id' => $providerGeneration['id'],
                'rates' => $providerGeneration['rates'] ?? $rates,
                'provider_created_at' => $providerGeneration['created_at'] ?? now(),
            ]);

            // The provider may have delivered a webhook before the create response arrived.
            app(WebhookProcessor::class)->replayPending($providerGeneration['id']);
        } catch (ConnectionException) {
            // The provider may have accepted the request even though the response was lost.
            // Keep the row queued; the scheduled reconciler retries with the same idempotency key.
            $generation->update([
                'provider_error_code' => 'submission_unconfirmed',
                'provider_error_message' => 'We could not confirm the provider response. We are safely checking this request; do not submit it again yet.',
            ]);

            return redirect()
                ->route('history.index')
                ->with('success', 'Your request is being reconciled with Pixelforge.');
        } catch (RequestException $exception) {
            $status = $exception->response?->status();

            // Rejected create requests are not billable. Keep a visible failed history record.
            $generation->update([
                'status' => 'failed',
                'provider_error_code' => $exception->response?->json('error.code') ?? 'provider_request_failed',
                'provider_error_message' => $exception->response?->json('error.message') ?? 'The provider rejected this request.',
            ]);

            $message = match ($status) {
                429 => 'Provider limit reached. Please wait and try again.',
                503 => 'Provider is temporarily overloaded. Please try again shortly.',
                default => 'The provider could not accept this generation.',
            };

            return back()->withErrors(['prompt' => $message]);
        }

        return redirect()
            ->route('history.index')
            ->with('success', 'Your image is queued.');
    }

    private function resource(Generation $generation): array
    {
        return [
            'id' => $generation->id,
            'prompt' => $generation->prompt,
            'size' => $generation->size,
            'quality' => $generation->quality,
            'status' => $generation->status->value,
            'estimated_cost' => $generation->estimated_cost,
            'actual_cost' => $generation->actual_cost,
            'provider_cost' => $generation->provider_cost,
            'image_url' => $generation->image_url,
            'image_url_expires_at' => $generation->image_url_expires_at?->toIso8601String(),
            'error' => $generation->provider_error_message,
            'created_at' => $generation->created_at->toIso8601String(),
        ];
    }
}
