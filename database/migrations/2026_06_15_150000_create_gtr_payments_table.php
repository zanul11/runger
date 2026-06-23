<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gtr_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gtr_registration_id')->constrained()->cascadeOnDelete();
            $table->string('order_id')->unique();
            $table->unsignedBigInteger('amount')->default(0);
            $table->string('status')->default('pending'); // pending|paid|cancelled|expired|failed
            $table->string('snap_token')->nullable();
            $table->text('snap_redirect_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->index(['gtr_registration_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtr_payments');
    }
};
