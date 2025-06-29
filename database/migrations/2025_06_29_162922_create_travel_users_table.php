<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('travel_users', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('telegram_id')->unique(); // ID пользователя в Telegram
            $table->string('name')->nullable();          // Имя из бота
            $table->json('test_answers')->nullable();    // Ответы на вопросы (JSON)
            $table->boolean('is_subscribed')->default(false); // Подписка на канал
            $table->bigInteger('invited_by')->nullable(); // Кто пригласил (referral)
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('travel_users');
    }
};
