<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('race_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('bib_number')->nullable();
            $table->string('name');
            $table->string('gender')->nullable(); // M / F
            $table->string('age_group')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('Indonesia');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('status')->default('registered'); // registered, paid, confirmed, dnf, dq
            $table->timestamp('registered_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
