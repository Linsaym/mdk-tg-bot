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
        ✨ Разыгрываем 1&nbsp;000&nbsp;000 Ozon баллов целый месяц до 20&nbsp;октября!<br>
Раз в две недели выбираем 10&nbsp;победителей – каждому подарим по 50&nbsp;000 баллов Ozon.<br>
Чем больше друзей вы пригласите — тем выше шансы на&nbsp;победу! Зовите всех и&nbsp;притягивайте удачу!<br>
<br>
На что потратить баллы:<br>
— Любые покупки на&nbsp;Ozon.<br>
— Продукты из&nbsp;Ozon fresh.<br>
— Отель, квартира или билеты через Ozon&nbsp;Travel.<br>
Курс выгодный 1&nbsp;балл = 1&nbsp;рубль.
",
        'winners' => "
        🎊Поздравляем победителей розыгрыша от Ozon Travel Vibe!<br>
Список счастливчиков: @linsaym и&nbsp;@diasspra<br>
Каждый победитель получает по 50&nbsp;000 Ozon-баллов! 🎉<br>
Следующий розыгрыш уже скоро — не&nbsp;упустите свой шанс!<br>
Чтобы участвовать в&nbsp;следующих розыгрышах автоматически, просто оставайтесь подписанными на&nbsp;<a href='https://t.me/+ogpsfRbwbSBkZTg6'>Telegram-канал Ozon&nbsp;Travel</a>.",
        'reminder' => "🔥 Розыгрыш 100&nbsp;000 баллов на&nbsp;двоих в&nbsp;Ozon Travel Vibe уже начался!<br>
Чем больше друзей с&nbsp;вами — тем выше шансы на&nbsp;победу! Зовите всех и&nbsp;притягивайте удачу!"
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