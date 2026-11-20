<?php

namespace Tests\Unit;

use App\Services\Money;
use App\Services\Pixelforge\PricingService;
use PHPUnit\Framework\TestCase;

class PricingServiceTest extends TestCase
{
    public function test_provider_cost_uses_exact_decimal_math_and_markup(): void
    {
        $cost = Money::tokenCost(6450, '40.00');

        $this->assertSame(258_000_000_000, $cost);
        $this->assertSame(322_500_000_000, Money::withMarkup($cost));
        $this->assertSame('0.322500000', Money::unitsToDecimal(Money::withMarkup($cost)));
    }

    public function test_estimate_uses_published_rates_and_reference_image_tokens(): void
    {
        $pricing = [
            'rates' => [
                'input_text_tokens' => '5.00',
                'input_image_tokens' => '10.00',
                'output_tokens' => '40.00',
                'reasoning_tokens' => '12.50',
            ],
            'tokens' => [
                'characters_per_text_token' => 4,
                'text_token_variance' => 0.15,
                'per_reference_image' => 765,
                'output' => [
                    'low' => ['1024x1024' => 280],
                    'medium' => ['1024x1024' => 1100],
                    'high' => ['1024x1024' => 4300],
                ],
                'reasoning' => [
                    'low' => ['min' => 0, 'max' => 500],
                    'medium' => ['min' => 200, 'max' => 2000],
                    'high' => ['min' => 1000, 'max' => 8000],
                ],
            ],
        ];

        $quote = (new PricingService())->estimate(
            'A small fox',
            '1024x1024',
            'medium',
            ['https://example.com/a.png'],
            $pricing,
        );

        $this->assertSame(765, $quote['usage_estimate']['input_image_tokens']);
        $this->assertSame(1100, $quote['usage_estimate']['output_tokens']);
        $this->assertSame(1100, $quote['usage_estimate']['reasoning_tokens']);
        $this->assertGreaterThan(0, $quote['customer_cost_units']);
        $this->assertSame(
            Money::withMarkup($quote['provider_cost_units']),
            $quote['customer_cost_units'],
        );
    }

    public function test_money_formats_only_supported_decimal_precision(): void
    {
        $this->assertSame('1.250000000', Money::unitsToDecimal(Money::decimalToUnits('1.25')));
        $this->expectException(\InvalidArgumentException::class);
        Money::decimalToUnits('-0.01');
    }
}
