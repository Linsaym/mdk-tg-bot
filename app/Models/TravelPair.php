<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelPair extends Model
{
    protected $table = 'travel_pairs';
    protected $fillable = [
        'user1_id',
        'user2_id',
        'compatibility_type',
        'score',
    ];

    public function user1()
    {
        return $this->belongsTo(TravelUser::class, 'user1_id');
    }

    public function user2()
    {
        return $this->belongsTo(TravelUser::class, 'user2_id');
    }

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'score' => 'integer',
    ];
}