<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = ['text', 'telegram_file_id'];

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public static function count()
    {
        return cache()->remember('questions_count', 3600, function () {
            return self::query()->count();
        });
    }
}
