<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TravelPair extends Model
{
    protected $table = 'travel_pairs';
    public $timestamps = false;
    protected $fillable = [
        'user1',
        'user2',
    ];

    public function user1()
    {
        return $this->belongsTo(TravelUser::class, 'user1_id');
    }

    public function user2()
    {
        return $this->belongsTo(TravelUser::class, 'user2_id');
    }
}