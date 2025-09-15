<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'inviter_id',
        'invited_id'
    ];

    public function inviter()
    {
        return $this->belongsTo(ContestParticipant::class, 'inviter_id');
    }

    public function invited()
    {
        return $this->belongsTo(ContestParticipant::class, 'invited_id');
    }
}