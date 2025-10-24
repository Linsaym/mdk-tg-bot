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
    protected $signature = 'notification:send {type=reminder} {--winners=} {--start-from=} {--end-from=} {--user-id=}';
    protected $description = 'Отправляет уведомление о начале розыгрыша всем пользователям';

    const MESSAGES = [
        'lottery' => "
        ✨ Разыгрываем 1&#160;000&#160;000 Ozon баллов целый месяц до 24&#160;октября!\n
Раз в две недели выбираем 10&#160;победителей – каждому подарим по 50&#160;000 баллов Ozon.\n
Чем больше друзей вы пригласите — тем выше шансы на&#160;победу! Зовите всех и&#160;притягивайте удачу!\n
🎉На что потратить баллы:\n
— Любые покупки на&#160;Ozon
— Продукты из&#160;Ozon fresh
— Отель, квартира или билеты через Ozon&#160;Travel\n
Курс выгодный 1&#160;балл = 1&#160;рубль.
",
        'winners' => "
        🎉Поздравляем победителей розыгрыша от Ozon Travel Vibe!\n
<b>Список счастливчиков: </b>
@Ml_not_available и @lyu243
@tima_fairy и @nastygrass
@DinaM070707 и @Plenakon
@KsenProkop и @Polinka0527
@Lubov_012 и @COCKA_NEGRA\n
Каждый победитель получает по 50&#160;000 Ozon-баллов! 🎉\n
<b>Проверить результаты можно <a href='https://drive.google.com/file/d/1atyslZxsqEXe5keuflhuxqA250jqPYa6/view?usp=sharing'>тут</a>.\n
Если вы не нашли себя среди победителей — не грустите! У вас есть крутой шанс взять реванш в &#160;<a href='https://t.me/ozontravel_official/5878'>новом розыгрыше 1 000 000 баллов Ozon!</a></b>.\n
🍀 Победителей будет 20: каждому — по 50 000 баллов! Удачи!
",
        'reminder' => "🔥 Розыгрыш 100&#160;000 баллов на&#160;двоих в&#160;Ozon Travel Vibe уже начался!\n
Чем больше друзей с&#160;вами — тем выше шансы на&#160;победу! Зовите всех и&#160;притягивайте удачу!"
    ];

    /**
     * @throws TelegramSDKException
     */
    public function handle(): void
    {
        $testBotToken = config('telegram.bots.trip-vibe-bot.token');
        $telegram = new Api($testBotToken);
        $successCount = 0;
        $errorCount = 0;

        $messageType = $this->argument('type');
        $winners = $this->option('winners');
        $startFromId = $this->option('start-from');
        $endFromId = $this->option('end-from');
        $userIdFilter = $this->option('user-id');

        $messageText = $this->getMessageText($messageType, $winners);

        $query = TravelUser::whereNotNull('telegram_id')
            ->whereNotNull('name')
            ->where('is_subscribed', '=', true)
            ->orderBy('id');

        if ($startFromId) {
            $query->where('id', '>=', $startFromId);
        }

        if ($endFromId) {
            $query->where('id', '<=', $endFromId);
        }

        //Если нам нужно отправить только одному юзеру по id
        if ($userIdFilter) {
            $query->where('id', $userIdFilter);
        }

        $telegramIds = $query->pluck('telegram_id', 'id');

        $batchSize = 20;
        $concurrentRequests = 4; // Количество одновременных запросов

        foreach ($telegramIds->chunk($batchSize) as $chunkIndex => $chunk) {
            $requests = [];

            // Подготавливаем все запросы для батча
            foreach ($chunk as $userId => $telegramId) {
                $params = [
                    'chat_id' => $telegramId,
                    'text' => $messageText,
                    'parse_mode' => 'HTML'
                ];

                if ($messageType == 'lottery') {
                    $params['reply_markup'] = json_encode([
                        'inline_keyboard' => [[['text' => '🎉 Участвовать', 'callback_data' => 'participate']]]
                    ]);
                } else {
                    $params['link_preview_options'] = json_encode(['is_disabled' => true]);
                }

                $requests[] = compact('userId', 'telegramId', 'params');
            }

            // Отправляем запросы пачками с минимальной задержкой
            foreach (array_chunk($requests, $concurrentRequests) as $requestChunk) {
                $promises = [];

                // Отправляем concurrentRequests запросов одновременно
                foreach ($requestChunk as $request) {
                    try {
                        $telegram->sendMessage($request['params']);
                        $successCount++;
                        $this->info('Успешно отправлено пользователю:' . $request['params']['chat_id']);
                    } catch (TelegramResponseException $e) {
                        $errorCount++;
                        $this->handleError($e, $request['telegramId'], $request['userId']);
                    }
                    // Между запросами в пачке
                    usleep(500000);
                }

                // Задержка между пачками concurrent запросов
                usleep(400000);
            }
        }

        $this->info("Рассылка завершена! Успешно: {$successCount}, Ошибок: {$errorCount}");
    }

    protected function handleError($exception, $telegramId, $userId = null): void
    {
        $userInfo = $userId ? "ID {$userId}, Telegram ID {$telegramId}" : "Telegram ID {$telegramId}";

        if (str_contains($exception->getMessage(), 'bot was blocked')) {
            //TravelUser::where('telegram_id', $telegramId)->update(['telegram_id' => null]);
            Log::warning("Пользователь {$userInfo} заблокировал бота");
        } elseif (str_contains($exception->getMessage(), 'Too Many Requests')) {
            Log::error("Rate limit для пользователя {$userInfo}");
            // Можно добавить паузу при rate limit
            sleep(1);
        } else {
            Log::error("Ошибка для пользователя {$userInfo}: " . $exception->getMessage());
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
