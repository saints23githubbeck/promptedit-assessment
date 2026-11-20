<?php

namespace App\Services\Pixelforge;

use App\Services\Money;
use InvalidArgumentException;

final class PricingService
{
    private const USAGE_KEYS = [
        'input_text_tokens',
        'input_image_tokens',
        'output_tokens',
        'reasoning_tokens',
    ];

    public function estimate(
        string $prompt,
        string $size,
        string $quality,
        array $referenceImages,
        array $pricing,
    ): array {
        $tokens = $pricing['tokens'] ?? null;
        $rates = $pricing['rates'] ?? null;

        if (!is_array($tokens) || !is_array($rates)) {
            throw new InvalidArgumentException('Pixelforge pricing response is incomplete.');
        }

        $charactersPerToken = (int) ($tokens['characters_per_text_token'] ?? 4);
        if ($charactersPerToken < 1) {
            throw new InvalidArgumentException('Invalid Pixelforge text-token estimate configuration.');
        }

        $textTokens = (int) ceil(mb_strlen($prompt) / $charactersPerToken);
        $textTokens = (int) ceil($textTokens * (1 + (float) ($tokens['text_token_variance'] ?? 0.15)));

        $reasoning = $tokens['reasoning'][$quality] ?? null;
        $outputTokens = $tokens['output'][$quality][$size] ?? null;

        if (!is_array($reasoning) || $outputTokens === null || !isset($tokens['per_reference_image'])) {
            throw new InvalidArgumentException('Unsupported generation options in Pixelforge pricing response.');
        }

        $usage = [
            'input_text_tokens' => $textTokens,
            'input_image_tokens' => count($referenceImages) * (int) $tokens['per_reference_image'],
            'output_tokens' => (int) $outputTokens,
            'reasoning_tokens' => (int) floor(((int) $reasoning['min'] + (int) $reasoning['max']) / 2),
        ];

        $providerCost = $this->cost($usage, $rates);

        return [
            'usage_estimate' => $usage,
            'provider_cost_units' => $providerCost,
            'customer_cost_units' => Money::withMarkup($providerCost),
        ];
    }

    public function actualCost(array $usage, array $rates): int
    {
        return $this->cost($usage, $rates);
    }

    private function cost(array $usage, array $rates): int
    {
        $total = 0;

        foreach (self::USAGE_KEYS as $key) {
            if (!array_key_exists($key, $rates)) {
                throw new InvalidArgumentException("Pixelforge rate snapshot is missing {$key}.");
            }

            $tokenCount = (int) ($usage[$key] ?? 0);
            $total += Money::tokenCost($tokenCount, (string) $rates[$key]);
        }

        return $total;
    }
}
