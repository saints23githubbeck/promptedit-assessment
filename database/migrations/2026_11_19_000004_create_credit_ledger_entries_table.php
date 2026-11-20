<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('credit_ledger_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('generation_id')->nullable()->constrained()->nullOnDelete();
            $t->string('type');
            $t->bigInteger('amount_units');
            $t->string('description');
            $t->timestamps();
            $t->index(['user_id', 'created_at']);
            $t->unique(['generation_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_ledger_entries');
    }
};
