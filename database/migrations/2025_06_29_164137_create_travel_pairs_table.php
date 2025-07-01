<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('travel_pairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user1_id')->constrained('travel_users');
            $table->foreignId('user2_id')->constrained('travel_users');
            $table->string('compatibility_type'); // Например, "Кот-сибарит + Турист-выживальщик"
            $table->integer('score');            // Процент совпадения (опционально)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_pairs');
    }
};
