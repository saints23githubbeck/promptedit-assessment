<?php
return ['pixelforge' => ['base_url' => env('PIXELFORGE_BASE_URL', 'https://pixelforge.promptedit.dev/v1'), 'api_key' => env('PIXELFORGE_API_KEY'),
    'webhook_secret' => env('PIXELFORGE_WEBHOOK_SECRET'), 'webhook_tolerance' => (int)env('PIXELFORGE_WEBHOOK_TOLERANCE', 300), 'timeout' => 15]];
