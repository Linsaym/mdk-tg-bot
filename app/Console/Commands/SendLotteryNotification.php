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
        ✨ Разыгрываем 1&#160;000&#160;000 Ozon баллов целый месяц до 20&#160;октября!\n
Раз в две недели выбираем 10&#160;победителей – каждому подарим по 50&#160;000 баллов Ozon.\n
Чем больше друзей вы пригласите — тем выше шансы на&#160;победу! Зовите всех и&#160;притягивайте удачу!\n
На что потратить баллы:\n
— Любые покупки на&#160;Ozon.\n
— Продукты из&#160;Ozon fresh.\n
— Отель, квартира или билеты через Ozon&#160;Travel.\n
Курс выгодный 1&#160;балл = 1&#160;рубль.
",
        'winners' => "
        🎊Поздравляем победителей розыгрыша от Ozon Travel Vibe!\n
Список счастливчиков: @linsaym и&#160;@diasspra\n
Каждый победитель получает по 50&#160;000 Ozon-баллов! 🎉\n
Следующий розыгрыш уже скоро — не&#160;упустите свой шанс!\n
Чтобы участвовать в&#160;следующих розыгрышах автоматически, просто оставайтесь подписанными на&#160;<a href='https://t.me/+ogpsfRbwbSBkZTg6'>Telegram-канал Ozon&#160;Travel</a>.",
        'reminder' => "🔥 Розыгрыш 100&#160;000 баллов на&#160;двоих в&#160;Ozon Travel Vibe уже начался!\n
Чем больше друзей с&#160;вами — тем выше шансы на&#160;победу! Зовите всех и&#160;притягивайте удачу!"
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
                    if ($messageType == 'lottery') {
                        $telegram->sendMessage([
                            'chat_id' => $telegramId,
                            'text' => $messageText,
                            'parse_mode' => 'HTML',
                            'reply_markup' => json_encode([
                                'inline_keyboard' => [
                                    [
                                        [
                                            'text' => '🎉 Участвовать',
                                            'callback_data' => 'participate'
                                        ],
                                    ],
                                ]
                            ])
                        ]);
                    } else {
                        $telegram->sendMessage([
                            'chat_id' => $telegramId,
                            'text' => $messageText,
                            'link_preview_options' => ['is_disabled' => true],
                            'parse_mode' => 'HTML'
                        ]);
                    }


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