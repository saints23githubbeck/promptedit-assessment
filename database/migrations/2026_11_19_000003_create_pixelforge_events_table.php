<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pixelforge_events', function (Blueprint $t) {
            $t->id();
            $t->string('event_id')->unique();
            $t->string('event_type');
            $t->string('generation_id')->nullable()->index();
            $t->json('payload');
            $t->timestamp('processed_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pixelforge_events');
    }
};
