<?php

namespace App\Http\Controllers;

use App\Services\Pixelforge\WebhookProcessor;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PixelforgeWebhookController
{
    public function __invoke(Request $request, WebhookProcessor $processor): Response
    {
        $rawBody = $request->getContent();
        $signature = $request->header('Pixelforge-Signature');

        abort_unless($processor->verify($rawBody, $signature), 400, 'Invalid Pixelforge signature.');

        $event = json_decode($rawBody, true);
        abort_unless(is_array($event), 400, 'Invalid JSON payload.');
        abort_unless(
            ($event['object'] ?? null) === 'event'
            && is_string($event['id'] ?? null)
            && $event['id'] !== '',
            400,
            'Invalid Pixelforge event.',
        );

        if (($event['type'] ?? null) === 'ping') {
            return response()->noContent();
        }

        abort_unless(is_array($event['data']['object'] ?? null), 400, 'Invalid generation event payload.');

        $processor->process($event);

        return response()->noContent();
    }
}
