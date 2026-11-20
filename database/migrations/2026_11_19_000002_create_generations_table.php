<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('generations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('provider_id')->nullable()->unique();
            $t->uuid('idempotency_key')->unique();
            $t->unsignedTinyInteger('submission_attempts')->default(1);
            $t->text('prompt');
            $t->string('size', 20);
            $t->string('quality', 10);
            $t->json('reference_images')->nullable();
            $t->string('status', 20)->index();
            $t->json('rates');
            $t->json('usage_estimate');
            $t->json('usage_actual')->nullable();
            $t->unsignedBigInteger('estimated_provider_cost_units');
            $t->unsignedBigInteger('estimated_customer_cost_units');
            $t->unsignedBigInteger('actual_provider_cost_units')->nullable();
            $t->unsignedBigInteger('actual_customer_cost_units')->nullable();
            $t->text('image_url')->nullable();
            $t->timestamp('image_url_expires_at')->nullable();
            $t->string('provider_error_code')->nullable();
            $t->text('provider_error_message')->nullable();
            $t->timestamp('provider_created_at')->nullable();
            $t->timestamp('provider_completed_at')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generations');
    }
};
