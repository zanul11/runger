<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gtr_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('runner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gtr_category_id')->constrained()->cascadeOnDelete();
            $table->string('bib_number')->nullable();
            $table->string('status')->default('pending'); // pending, paid, confirmed, cancelled
            $table->timestamp('registered_at')->nullable();
            $table->timestamps();

            $table->unique(['runner_id', 'gtr_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtr_registrations');
    }
};
