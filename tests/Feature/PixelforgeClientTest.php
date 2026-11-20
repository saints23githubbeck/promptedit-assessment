<?php

namespace Tests\Feature;

use App\Services\Pixelforge\Client;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PixelforgeClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.pixelforge.base_url' => 'https://pixelforge.promptedit.dev/v1',
            'services.pixelforge.api_key' => 'pf_test_key',
        ]);
    }

    public function test_pricing_is_requested_without_authentication(): void
    {
        Http::fake([
            'https://pixelforge.promptedit.dev/v1/pricing' => Http::response([
                'object' => 'pricing',
                'rates' => ['input_text_tokens' => '5.00'],
                'tokens' => ['characters_per_text_token' => 4],
            ]),
        ]);

        $pricing = app(Client::class)->pricing();

        $this->assertSame('5.00', $pricing['rates']['input_text_tokens']);
        Http::assertSent(fn (Request $request) =>
            $request->url() === 'https://pixelforge.promptedit.dev/v1/pricing'
            && !$request->hasHeader('Authorization')
        );
    }

    public function test_create_sends_bearer_token_and_idempotency_key(): void
    {
        Http::fake([
            'https://pixelforge.promptedit.dev/v1/generations' => Http::response([
                'id' => 'gen_test_123',
                'object' => 'generation',
                'status' => 'queued',
                'rates' => ['output_tokens' => '40.00'],
            ], 202),
        ]);

        $generation = app(Client::class)->create(
            ['prompt' => 'A test image', 'size' => '1024x1024', 'quality' => 'medium'],
            'local-idempotency-key',
        );

        $this->assertSame('gen_test_123', $generation['id']);
        Http::assertSent(fn (Request $request) =>
            $request->url() === 'https://pixelforge.promptedit.dev/v1/generations'
            && $request->hasHeader('Authorization', 'Bearer pf_test_key')
            && $request->hasHeader('Idempotency-Key', 'local-idempotency-key')
        );
    }

    public function test_usage_sends_requested_utc_date_range(): void
    {
        Http::fake([
            'https://pixelforge.promptedit.dev/v1/usage*' => Http::response([
                'data' => [],
                'total' => ['cost_usd' => '0.000000000'],
            ]),
        ]);

        $usage = app(Client::class)->usage('2026-11-01', '2026-11-30');

        $this->assertSame('0.000000000', $usage['total']['cost_usd']);
        Http::assertSent(fn (Request $request) =>
            str_starts_with($request->url(), 'https://pixelforge.promptedit.dev/v1/usage?')
            && str_contains($request->url(), 'start_date=2026-11-01')
            && str_contains($request->url(), 'end_date=2026-11-30')
        );
    }
}
