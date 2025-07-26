<?php

use Database\Seeders\TelegramMessagesSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('telegram_messages', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // Ключ сообщения (например, 'greetings')
            $table->string('group')->index(); // Группа сообщений (например, 'welcome', 'subscription')
            $table->text('text'); // Текст сообщения
            $table->integer('order')->default(0); // Порядок сортировки
            $table->timestamps();
        });

        $seeder = new TelegramMessagesSeeder();
        $seeder->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_messages');
    }
};
