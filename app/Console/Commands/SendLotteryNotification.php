<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TravelUser;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramResponseException;
use Telegram\Bot\Exceptions\TelegramSDKException;

class SendLotteryNotification extends Command
{
    protected $signature = 'notification:lottery-start';
    protected $description = 'Отправляет уведомление о начале розыгрыша всем пользователям';

    /**
     * @throws TelegramSDKException
     */
    public function handle(): void
    {
        //поменять конфиг и бд
        config(['database.default' => 'mysql_test']);
        $testBotToken = config('telegram.bots.test.token');
        $telegram = new Api($testBotToken);
        $successCount = 0;
        $errorCount = 0;

        // Получаем только telegram_id для экономии памяти
        $telegramIds = TravelUser::whereNotNull('telegram_id')
            ->where('telegram_id', '!=', '')
            ->pluck('telegram_id');

        $this->info("Найдено пользователей: " . $telegramIds->count());

        $batchSize = 30; // Размер батча
        $delayBetweenBatches = 1; // Задержка между батчами в секундах

        foreach ($telegramIds->chunk($batchSize) as $chunk) {
            foreach ($chunk as $telegramId) {
                try {
                    $telegram->sendMessage([
                        'chat_id' => $telegramId,
                        'text' => '🎉 Начался розыгрыш! Участвуйте и выигрывайте призы! 🎊',
                        'parse_mode' => 'HTML'
                    ]);

                    $successCount++;
                    $this->info("Отправлено: {$telegramId}");
                } catch (TelegramResponseException $e) {
                    $errorCount++;
                    $this->handleError($e, $telegramId);
                }

                // Минимальная задержка между сообщениями
                usleep(50000); // 0.05 секунды
            }

            // Задержка между батчами
            if ($delayBetweenBatches > 0) {
                sleep($delayBetweenBatches);
            }
        }

        $this->info("Рассылка завершена! Успешно: {$successCount}, Ошибок: {$errorCount}");
    }

    protected function handleError($exception, $telegramId)
    {
        $errorMessage = "Ошибка для пользователя {$telegramId}: " . $exception->getMessage();
        $this->error($errorMessage);
        Log::error($errorMessage);

        // Можно добавить логику для блокировок
        if (str_contains($exception->getMessage(), 'bot was blocked')) {
            TravelUser::where('telegram_id', $telegramId)->update(['telegram_id' => null]);
            $this->warn("Пользователь заблокировал бота, telegram_id обнулен");
        }
    }
}