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
    protected $signature = 'notification:send {type=reminder} {--winners=}';
    protected $description = 'Отправляет уведомление о начале розыгрыша всем пользователям';

    const MESSAGES = [
        'lottery' => "
    ✨ А вы готовы поймать удачу? Ozon Travel Vibe разыгрывает 1 000 000 Ozon-баллов целый месяц до 7 октября!\n\nКаждые две недели выбираем 5 пар участников. Каждому победителю — по 50 000 баллов.\n\nУдвойте. Утройте! Учетверите! Шансы на победу — зовите участвовать всех, кто не меньше вас заслужил отдых!\n\nКак воспользоваться призом? Можно отправиться вместе в путешествие, затариться едой на Ozon Fresh для праздничной вечеринки в честь победы или купить что-то классное на Ozon💙
    ",
        'winners' => "
    🎊 Поздравляем победителей розыгрыша от Ozon Travel Vibe!\n\nСписок счастливчиков: %winners%\n\nКаждый победитель получает по 50 000 Ozon-баллов! 🎉\n\nСледующий розыгрыш уже скоро - не упустите свой шанс!
    ",
        'reminder' => "🔥 Напоминаем: чтобы участвовать в розыгрыше и увеличить шансы, пригласите ещё друзей в Ozon Travel Vibe! Каждый новый друг — это дополнительный шанс стать победителем и получить 50 000 Ozon-баллов. 🚀
    "
    ];

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

        $messageType = $this->argument('type');
        $winners = $this->option('winners');

        // Получите текст сообщения
        $messageText = $this->getMessageText($messageType, $winners);

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
                        'text' => $messageText,
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

    protected function handleError($exception, $telegramId): void
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

    protected function getMessageText(string $type, ?string $winners = null): string
    {
        $text = self::MESSAGES[$type] ?? self::MESSAGES['lottery'];

        if ($type === 'winners' && $winners) {
            $text = str_replace('%winners%', "@linsaym и @diasspra", $text);
        }

        return $text;
    }
}