<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('travel_pairs');
        Schema::create('travel_pairs', function (Blueprint $table) {
            $table->id();
            $table->string('user1');
            $table->string('user2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_pairs');
        Schema::create('travel_pairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user1_id')->constrained('travel_users');
            $table->foreignId('user2_id')->constrained('travel_users');
            $table->string('compatibility_type');
            $table->integer('score');
            $table->timestamps();
        });
    }
};
