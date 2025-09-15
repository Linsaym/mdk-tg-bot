<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContestParticipant extends Model
{
    use HasFactory;

    protected $table = 'contest_participants';

    protected $fillable = [
        'telegram_id',
        'username',
        'first_name',
        'last_name',
        'accepted_terms',
        'accepted_terms_at'
    ];

    protected $casts = [
        'accepted_terms' => 'boolean',
        'accepted_terms_at' => 'datetime'
    ];

    public function winners()
    {
        return $this->hasMany(Winner::class, 'participant_id');
    }

    public function invitations()
    {
        return $this->hasMany(Invitation::class, 'inviter_id');
    }
}