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
        // Таблица участников конкурса
        Schema::create('contest_participants', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('telegram_id')->unique();
            $table->string('username')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->boolean('accepted_terms')->default(false);
            $table->timestamp('accepted_terms_at')->nullable();
            $table->timestamps();

            $table->index('telegram_id');
            $table->index('accepted_terms');
        });

        // Таблица розыгрышей
        Schema::create('contests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('winners_count')->default(10);
            $table->integer('prize_amount')->default(50000);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index('start_date');
            $table->index('end_date');
        });

        // Таблица победителей
        Schema::create('winners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contest_id')->constrained()->onDelete('cascade');
            $table->foreignId('participant_id')->constrained('contest_participants')->onDelete('cascade');
            $table->string('prize_code')->unique();
            $table->integer('prize_amount');
            $table->timestamp('awarded_at')->useCurrent();
            $table->timestamps();

            $table->index('prize_code');
            $table->index('contest_id');
            $table->index('participant_id');
        });

        // Таблица для хранения отправленных уведомлений
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // Тип уведомления: reminder, announcement, winner_notification
            $table->text('message');
            $table->integer('recipients_count')->default(0);
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamps();

            $table->index('type');
            $table->index('sent_at');
        });

        // Таблица для отслеживания приглашений
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inviter_id')->constrained('contest_participants')->onDelete('cascade');
            $table->foreignId('invited_id')->constrained('contest_participants')->onDelete('cascade');
            $table->timestamp('invited_at')->useCurrent();
            $table->timestamps();

            $table->unique(['inviter_id', 'invited_id']);
            $table->index('inviter_id');
            $table->index('invited_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('winners');
        Schema::dropIfExists('contests');
        Schema::dropIfExists('contest_participants');
    }
};