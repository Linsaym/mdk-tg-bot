<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramMessage extends Model
{
    protected $fillable = ['key', 'group', 'text', 'order'];

    protected $casts = [
        'order' => 'integer',
    ];

    public function scopeGroup($query, $group)
    {
        return $query->where('group', $group)->orderBy('order');
    }
}
