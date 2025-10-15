<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TravelUser;
use App\Models\TravelPair;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Log;

class GenerateTravelPairs extends Command
{
    protected $signature = 'travel:pairs:generate';
    protected $description = 'Generate travel pairs from users with invited_by relationship';

    public Api $telegram;

    /**
     * @throws TelegramSDKException
     */
    public function handle(): int
    {
        $token = config('telegram.bots.trip-vibe-bot.token');
        $this->telegram = new Api($token);

        $this->info('Starting travel pairs generation...');

        // Дата фильтрации (берём пользователей, только с этого числа)
        $cutoffDate = '2025-10-01 00:00:00';

        // Получаем пользователей, которые соответствуют условиям
        $eligibleUsers = TravelUser::where('updated_at', '>', $cutoffDate)
            ->where('is_subscribed', true)
            ->whereNotNull('test_answers')
            ->whereNotNull('invited_by')
            ->get();

        $this->info("Found {$eligibleUsers->count()} eligible users with invited_by");

        $pairsCreated = 0;
        $errors = 0;

        foreach ($eligibleUsers as $invitedUser) {
            try {
                // Находим пользователя, который пригласил (просто по telegram_id)
                $inviter = TravelUser::where('telegram_id', $invitedUser->invited_by)->first();

                if (!$inviter) {
                    $this->warn("Inviter not found for user: {$invitedUser->telegram_id}");
                    continue;
                }

                // Проверяем, существует ли уже такая пара
                $existingPair = TravelPair::where(function ($query) use ($inviter, $invitedUser) {
                    $query->where('user1', $inviter->telegram_id)
                        ->where('user2', $invitedUser->telegram_id);
                })->orWhere(function ($query) use ($inviter, $invitedUser) {
                    $query->where('user1', $invitedUser->telegram_id)
                        ->where('user2', $inviter->telegram_id);
                })->first();

                if ($existingPair) {
                    $this->info("Pair already exists: {$inviter->telegram_id} - {$invitedUser->telegram_id}");
                    continue;
                }

                // Получаем никнеймы через getUserInfo с задержкой
                $inviterUsername = $this->getUsernameWithDelay($inviter->telegram_id);
                $invitedUsername = $this->getUsernameWithDelay($invitedUser->telegram_id);

                // Если не получилось получить никнейм - пропускаем пару
                if (!$inviterUsername || !$invitedUsername) {
                    $this->error(
                        "Failed to get usernames for pair: {$inviter->telegram_id} - {$invitedUser->telegram_id}"
                    );
                    $errors++;
                    continue;
                }

                // Создаем пару только с никнеймами
                TravelPair::create([
                    'user1' => $inviterUsername,
                    'user2' => $invitedUsername,
                ]);

                $pairsCreated++;
                $this->info("Created pair: {$inviterUsername} - {$invitedUsername}");
            } catch (\Exception $e) {
                $this->error("Error processing user {$invitedUser->telegram_id}: " . $e->getMessage());
                Log::error("Error generating travel pair", [
                    'user_id' => $invitedUser->telegram_id,
                    'error' => $e->getMessage()
                ]);
                $errors++;
            }
        }

        $this->info("Pairs generation completed. Created: {$pairsCreated}, Errors: {$errors}");

        return Command::SUCCESS;
    }

    /**
     * Получает username пользователя с задержкой
     */
    private function getUsernameWithDelay($telegramId)
    {
        try {
            // Задержка 0.5 секунды
            usleep(500000);

            $response = $this->telegram->getChat([
                'chat_id' => $telegramId
            ]);

            return $response['username'];
        } catch (\Exception $e) {
            $this->error("Error getting user info for {$telegramId}: " . $e->getMessage());
            Log::error("Error getting Telegram user info", [
                'telegram_id' => $telegramId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}