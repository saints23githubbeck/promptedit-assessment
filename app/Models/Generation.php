<?php

namespace App\Models;

use App\Enums\GenerationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Generation extends Model
{
    protected $fillable = ['user_id', 'provider_id', 'idempotency_key', 'submission_attempts', 'prompt', 'size', 'quality', 'reference_images', 'status', 'rates', 'usage_estimate', 'usage_actual', 'estimated_provider_cost_units', 'estimated_customer_cost_units', 'actual_provider_cost_units', 'actual_customer_cost_units', 'image_url', 'image_url_expires_at', 'provider_error_code', 'provider_error_message', 'provider_created_at', 'provider_completed_at'];

    protected function casts(): array
    {
        return ['status' => GenerationStatus::class, 'reference_images' => 'array', 'rates' => 'array', 'usage_estimate' => 'array', 'usage_actual' => 'array', 'image_url_expires_at' => 'immutable_datetime', 'provider_created_at' => 'immutable_datetime', 'provider_completed_at' => 'immutable_datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getEstimatedCostAttribute(): string
    {
        return \App\Services\Money::unitsToDecimal((int)$this->estimated_customer_cost_units);
    }

    public function getActualCostAttribute(): ?string
    {
        return $this->actual_customer_cost_units === null ? null : \App\Services\Money::unitsToDecimal((int)$this->actual_customer_cost_units);
    }

    public function getProviderCostAttribute(): ?string
    {
        return $this->actual_provider_cost_units === null ? null : \App\Services\Money::unitsToDecimal((int)$this->actual_provider_cost_units);
    }
}
