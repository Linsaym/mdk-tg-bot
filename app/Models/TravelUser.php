<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(TravelUser::class, 'invited_by', 'telegram_id');
    }

    public function invitedUsers(): HasMany|TravelUser
    {
        return $this->hasMany(TravelUser::class, 'invited_by', 'telegram_id');
    }

    public function hasCompletedTest(): bool
    {
        if (!$this->test_answers) return false;

        $answers = json_decode($this->test_answers, true);
        return count($answers) === Question::count();
    }
}
