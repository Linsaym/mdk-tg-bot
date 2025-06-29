<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TravelUser extends Model
{
    protected $table = 'travel_users';
    protected $fillable = [
        'telegram_id',
        'name',
        'test_answers',
        'is_subscribed',
        'invited_by'
    ];
    protected $casts = [
        'test_answers' => 'array', // Автоматическая конвертация JSON ↔ массив
    ];
}
