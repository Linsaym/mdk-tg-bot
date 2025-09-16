<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('travel_users', function (Blueprint $table) {
            $table->integer('first_friend_id')->default(false);
            $table->integer('invited_friends_count')->default(0)->after('first_friend_id');
            $table->boolean('participate_in_lottery')->default(false)->after('invited_friends_count');
        });
    }

    public function down(): void
    {
        Schema::table('travel_users', function (Blueprint $table) {
            $table->dropColumn(['invited_friends_count', 'participate_in_lottery', 'first_friend_id']);
        });
    }
};
