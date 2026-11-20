<?php

namespace Tests\Feature;

use Tests\TestCase;

class PixelforgeWebhookControllerTest extends TestCase
{
    public function test_signed_ping_webhook_is_accepted_without_csrf_token(): void
    {
        config(['services.pixelforge.webhook_secret' => 'whsec_test']);

        $timestamp = time();
        $body = json_encode([
            'id' => 'evt_ping_test',
            'object' => 'event',
            'type' => 'ping',
        ], JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $timestamp . '.' . $body, 'whsec_test');

        $response = $this->call(
            'POST',
            '/webhooks/pixelforge',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_PIXELFORGE_SIGNATURE' => "t={$timestamp},v1={$signature}",
            ],
            $body,
        );

        $response->assertNoContent();
    }

    public function test_unsigned_webhook_is_rejected(): void
    {
        config(['services.pixelforge.webhook_secret' => 'whsec_test']);

        $response = $this->call(
            'POST',
            '/webhooks/pixelforge',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"id":"evt_unsigned","object":"event","type":"ping"}',
        );

        $response->assertBadRequest();
    }
}
