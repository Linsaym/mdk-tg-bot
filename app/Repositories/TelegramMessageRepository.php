<?php

namespace App\Repositories;

use App\Models\TelegramMessage;
use Illuminate\Support\Facades\Cache;

class TelegramMessageRepository
{
    protected int $cacheTime = 3600; // 1 час

    public function getMessagesByGroup(string $group): array
    {
        return Cache::remember("telegram_messages.{$group}", $this->cacheTime, function () use ($group) {
            return TelegramMessage::group($group)
                ->get()
                ->pluck('text')
                ->toArray();
        });
    }

    public function getRandomMessageFromGroup(string $group): ?string
    {
        $messages = $this->getMessagesByGroup($group);
        return $messages[array_rand($messages)] ?? null;
    }

    public function clearCacheForGroup(string $group): void
    {
        Cache::forget("telegram_messages.{$group}");
    }
}