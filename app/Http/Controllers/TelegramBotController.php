<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\TravelUser;
use App\Models\Question;
use Telegram\Bot\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Exceptions\TelegramSDKException;

class TelegramBotController extends Controller
{
    private Api $telegram;


    public array $greetings = [
        "Привет! Это не просто тест — это бот от Ozon Travel. Давайте проверим, насколько вы с другом подходите друг другу для путешествий.",
        "Это бот от Ozon Travel — и он покажет, с кем реально круто поехать отдыхать, а с кем лучше просто мемами пообмениваться.",
        "Партнёр в отпуске — это вам не шутки! Бот от Ozon Travel определит, кто ваш друг: любитель моря или горных вершин.",
        "Этот тест от бота Ozon Travel покажет: вы идеальная travel-парочка или лучше разъехаться по разным курортам?",
        "Ты + друг + билеты в руках. Но совпадаете ли вы по отпускному вайбу? Бот от Ozon Travel поможет разобраться."
    ];

    // Общая инструкция
    public string $instructions = "\n\nЧто нужно сделать:\n"
    . "1. Пройдите сначала тест самостоятельно.\n"
    . "2. Поделитесь ссылкой на тест с друзьями.\n"
    . "3. После прохождения вы узнаете, подходите ли вы для совместных поездок или ваши предпочтения слишком разные по вайбу";


    public function __construct(Api $telegram)
    {
        $this->telegram = $telegram;
    }

    private function getRandomGreetingWithInstructions(): string
    {
        return $this->greetings[array_rand($this->greetings)] . $this->instructions;
    }

    /**
     * @throws TelegramSDKException
     */
    public function handleWebhook(Request $request)
    {
        $update = $this->telegram->getWebhookUpdate();
        Log::info('Webhook update:', $update->toArray());

        $chatId = $update->getChat()?->id;
        $message = $update->getMessage();
        $callbackQuery = $update->getCallbackQuery();

        // Обработка callback-кнопок
        if ($callbackQuery) {
            $this->handleCallbackQuery($callbackQuery);
            return response()->json(['status' => 'ok']);
        }

        // Обработка текстовых сообщений
        if ($message && $text = $message->text) {
            $text_split = explode(' ', $text);

            $user = TravelUser::firstOrCreate(['telegram_id' => $chatId]);

            if ($text === "/code") {  // Добавлено
                $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => "Ваш код: `$chatId`"]);  // Добавлено
                return response()->json(['status' => 'ok']);  // Добавлено
            } else {
                if (str_starts_with($text, '/start')) {
                    // Передаем полный текст команды
                    $this->handleStartCommand($chatId, $user, $text);
                } else {
                    if ($text === "Пригласить другого друга") {
                        $this->telegram->sendMessage(
                            ['chat_id' => $chatId, 'text' => "Просто отправь ему свой код: `$chatId`"]
                        );
                    } else {
                        if ($text === "Начать тест заново") {
                            $this->telegram->sendMessage(
                                [
                                    'chat_id' => $chatId,
                                    'text' => "Напиши `/start 123` (вместо 123 код того кто вас пригласил)"
                                ]
                            );
                        } else {
                            if (str_starts_with($text, 'Я')) {
                                $this->saveUserName($chatId, $user, $text_split[1]);
                            } elseif (!$user->name) {
                                $this->askForName($chatId);
                            } else {
                                // Обработка случайных сообщений
                                $this->sendHintMessage($chatId);
                            }
                        }
                    }
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * @throws TelegramSDKException
     */
    private function handleStartCommand($chatId, TravelUser $user, $commandText)
    {
        // Парсим параметры из команды /start
        $this->processInvitation($user, $commandText);

        // Проверка подписки
        $isSubscribed = $this->checkSubscription($chatId);

        if (!$isSubscribed) {
            $this->askForSubscription($chatId);
            return;
        }

        // Если подписан, но имя не указано
        if (!$user->name) {
            $this->askForName($chatId);
            return;
        }


        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $this->getRandomGreetingWithInstructions()
        ]);
        // Если подписан и имя есть — начинаем тест
        $this->sendFirstQuestion($chatId);
    }

    /**
     * @throws TelegramSDKException
     */
    private function processInvitation(TravelUser $user, $commandText)
    {
        // Извлекаем параметр из команды /start
        $parts = explode(' ', $commandText);
//
        if (count($parts) > 2) {
            $this->telegram->sendMessage(
                ['chat_id' => $user->telegram_id, 'text' => 'Кажется ваше ссылка неверного формата']
            );
        }
        if (count($parts) == 2) {
            $inviterId = $parts[1];
            // Проверяем что пригласитель существует
            $inviterExists = TravelUser::where('telegram_id', $inviterId)->exists();
            if ($inviterExists && $inviterId != $user->telegram_id) {
                $user->update(['invited_by' => $inviterId]);
                Log::info("User {$user->telegram_id} invited by {$inviterId}");
            }
        } else {
            $this->telegram->sendMessage(
                ['chat_id' => $user->telegram_id, 'text' => 'Получается вас никто не пригласил...']
            );
            $this->telegram->sendMessage(
                [
                    'chat_id' => $user->telegram_id,
                    'text' => 'Но если всё таки пригласили, вы можете рестартнуть. Просто напишите `/start 123` (вместо 123 код вашего друга)'
                ]
            );
        }
    }

    /**
     * @throws TelegramSDKException
     */
    private function askForSubscription($chatId)
    {
        $messages = [
            "😅 Ой! Похоже, вы ещё не подписаны на канал Ozon Travel. Подпишитесь, чтобы открыть доступ к тесту!",
            "⏳ Упс! Без подписки на канал Ozon Travel не получится начать. Нажмите «Подписаться» — и сразу продолжаем!",
            "🚀 Почти готовы к старту! Остался один шаг — обязательно подпишитесь на канал Ozon Travel и возвращайтесь сюда.",
            "📌 Подпишитесь на Ozon Travel, чтобы пройти тест и узнать свой отпускной вайб! Без подписки — никак.",
            "💙 Чтобы продолжить, обязательно подпишитесь на канал Ozon Travel. И давайте выясним, как вы и друзья отдыхаете душой!"
        ];

        $randomMessage = $messages[array_rand($messages)];

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $randomMessage,
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => 'Подписаться', 'url' => 'https://t.me/ozontravel_official']],
                    [['text' => 'Я подписался!', 'callback_data' => 'check_subscription']]
                ]
            ])
        ]);
    }

    /**
     * @throws TelegramSDKException
     */
    private function askForName($chatId)
    {
        $messages = config('telegram_messages.name_request_messages');
        $randomMessage = $messages[array_rand($messages)];


        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => TravelUser::firstWhere(
                'telegram_id',
                $chatId
            )?->is_subscribed ? "Это бот от Ozon Travel" : $randomMessage
        ]);

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Перед тем, как отправиться в путешествие, расскажите, как вас зовут! Напишите сообщение в формате 'Я ***' "
        ]);
    }

    private function checkSubscription($chatId)
    {
        try {
            $response = $this->telegram->getChatMember([
                'chat_id' => '@ozontravel_official',
                'user_id' => $chatId
            ]);
            return in_array($response->status, ['member', 'administrator', 'creator']);
        } catch (\Exception $e) {
            Log::error("Ошибка проверки подписки: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @throws TelegramSDKException
     */
    private function saveUserName($chatId, TravelUser $user, $name)
    {
        $user->update(['name' => $name]);
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $this->getRandomGreetingWithInstructions(),
            'reply_markup' => json_encode([
                'inline_keyboard' => [[['text' => 'Начать тест', 'callback_data' => 'start_test']]]
            ])
        ]);
    }

    /**
     * @throws TelegramSDKException
     */
    private function sendFirstQuestion($chatId)
    {
        $question = Question::with('answers')->first();
        $this->sendQuestion($chatId, $question);
    }

    /**
     * @throws TelegramSDKException
     */
    private function sendQuestion($chatId, Question $question)
    {
        $keyboard = $question->answers->map(function ($answer) use ($question) {
            return [['text' => $answer->text, 'callback_data' => "answer_{$question->id}_{$answer->id}"]];
        })->toArray();

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Вопрос: " . $question->text,
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
    }

    /**
     * @throws TelegramSDKException
     */
    private function handleCallbackQuery($callbackQuery)
    {
        $chatId = $callbackQuery->getMessage()->getChat()->id;
        $messageId = $callbackQuery->getMessage()->getMessageId(); // Получаем ID сообщения
        $callbackQueryId = $callbackQuery->getId();
        $data = $callbackQuery->data;

        // Всегда отвечаем на callback, чтобы убрать "часики"
        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $callbackQueryId
        ]);

        $user = TravelUser::firstOrCreate(['telegram_id' => $chatId]);

        switch ($data) {
            case 'check_subscription':
                $this->handleSubscriptionCheck($chatId, $user);
                break;

            case 'start_test':
                $this->sendFirstQuestion($chatId);
                break;

            case 'restart_test':
                $this->askForName($chatId);
                break;

            default:
                if (str_starts_with($data, 'answer_')) {
                    $this->removeInlineButtons($chatId, $messageId);
                    $this->handleAnswer($chatId, $data, $user);
                }
                break;
        }
    }

    private function removeInlineButtons($chatId, $messageId)
    {
        try {
            $this->telegram->editMessageReplyMarkup([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'reply_markup' => json_encode(['inline_keyboard' => []])
            ]);
        } catch (\Exception $e) {
            Log::error("Ошибка при удалении кнопок: " . $e->getMessage());
        }
    }

    /**
     * @throws TelegramSDKException
     */
    private function handleSubscriptionCheck($chatId, TravelUser $user)
    {
        $isSubscribed = $this->checkSubscription($chatId);

        if ($isSubscribed) {
            $user->update(['is_subscribed' => true]);

            if (!$user->name) {
                $this->askForName($chatId);
            } else {
                $this->sendFirstQuestion($chatId);
            }
        } else {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Вы всё ещё не подписаны. Пожалуйста, подпитесь на канал и нажмите 'Я подписался!' снова."
            ]);
        }
    }

    /**
     * @throws TelegramSDKException
     */
    private function handleAnswer($chatId, $callbackData, TravelUser $user)
    {
        [$_, $questionId, $answerId] = explode('_', $callbackData);
        $answer = Answer::find($answerId);

        // Сохраняем ответ
        $answers = $user->test_answers ? json_decode($user->test_answers, true) : [];
        $answers[$questionId] = $answerId;
        $user->update(['test_answers' => json_encode($answers)]);

        // Отправляем реакцию
        if ($answer->reaction) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $answer->reaction
            ]);
        }

        // Следующий вопрос
        $nextQuestion = Question::where('id', '>', $questionId)->first();
        if ($nextQuestion) {
            $this->sendQuestion($chatId, $nextQuestion);
        } else {
            $this->completeTest($chatId, $user);
        }
    }

    /**
     * @throws TelegramSDKException
     */
    private function completeTest($chatId, TravelUser $user, $messageId = null)
    {
        if ($messageId) {
            $this->removeInlineButtons($chatId, $messageId);
        }

        // Генерируем реферальную ссылку
        $refLink = "https://t.me/trip_vibe_bot?start=" . $user->telegram_id;

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Тест завершён! Пригласите друга, чтобы узнать совместимость:",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => 'Пройти тест заново', 'callback_data' => 'restart_test']],
                    [['text' => 'Поделиться ссылкой', 'url' => "https://t.me/share/url?url=" . urlencode($refLink)]]
                ]
            ])
        ]);

        // Проверяем связи приглашения
        $this->checkInvitationRelationships($user);
    }

    /**
     * @throws TelegramSDKException
     */
    private function checkInvitationRelationships(TravelUser $user)
    {
        // 1. Проверяем, был ли этот пользователь приглашен кем-то
        if ($user->invited_by) {
            $this->checkAndSendCompatibility($user);
        }

        // 2. Проверяем, есть ли пользователи, приглашенные этим пользователем
        $invitedUsers = TravelUser::where('invited_by', $user->telegram_id)->get();

        foreach ($invitedUsers as $invitedUser) {
            if ($invitedUser->hasCompletedTest()) {
                $this->checkAndSendCompatibility($invitedUser);
            }
        }
    }

    /**
     * @throws TelegramSDKException
     */
    private function checkAndSendCompatibility(TravelUser $invitedUser)
    {
        // Находим пользователя, который пригласил
        $inviter = TravelUser::where('telegram_id', $invitedUser->invited_by)->first();

        if (!$inviter) {
            return;
        }

        // Проверяем, что оба завершили тест
        if ($invitedUser->hasCompletedTest() && $inviter->hasCompletedTest()) {
            // Рассчитываем совместимость
            $compatibility = $this->calculateCompatibility($inviter, $invitedUser);

            // Отправляем результат обоим пользователям
            try {
                $this->sendCompatibilityResult($inviter->telegram_id, $invitedUser, $compatibility);
                $this->sendCompatibilityResult($invitedUser->telegram_id, $inviter, $compatibility);
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }
        }
    }

    private function calculateCompatibility(TravelUser $user1, TravelUser $user2)
    {
        $answers1 = json_decode($user1->test_answers, true);
        $answers2 = json_decode($user2->test_answers, true);

        $totalQuestions = count($answers1);
        $matchingAnswers = 0;

        foreach ($answers1 as $questionId => $answerId) {
            if (isset($answers2[$questionId]) && $answers2[$questionId] == $answerId) {
                $matchingAnswers++;
            }
        }

        $percentage = round(($matchingAnswers / $totalQuestions) * 100);

        // Определяем тип совместимости на основе процента
        if ($percentage >= 80) {
            return 'Вы идеальная travel-пара! 🌟';
        } elseif ($percentage >= 60) {
            return 'Хорошая совместимость! Отдых будет отличным 👍';
        } elseif ($percentage >= 40) {
            return 'Средняя совместимость. Нужно договариваться! 🤝';
        } else {
            return 'Совместимость низкая. Возможно, лучше отдыхать отдельно? 😅';
        }
    }

    /**
     * @throws TelegramSDKException
     */
    private function sendCompatibilityResult($chatId, TravelUser $partner, $compatibilityText)
    {
        try {
            $partnerName = $partner->name ?: 'Ваш друг';

            $message = "🎉 Результат совместимости с $partnerName!\n\n";
            $message .= "{$compatibilityText}\n\n";
            $message .= "Вы можете пройти тест с другими друзьями, чтобы сравнить результаты!";

            $refLink = "https://t.me/trip_vibe_bot?start=" . $chatId;

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            [
                                'text' => 'Поделиться ссылкой',
                                'url' => "https://t.me/share/url?url=" . urlencode($refLink)
                            ]
                        ],
                        [['text' => 'Пройти тест заново', 'callback_data' => 'restart_test']]
                    ]
                ])
            ]);
        } catch (\Telegram\Bot\Exceptions\TelegramResponseException $e) {
            if (str_contains($e->getMessage(), 'chat not found')) {
                Log::warning(
                    "Не удалось отправить результат совместимости: пользователь $chatId заблокировал бота или чат не существует"
                );
            } else {
                Log::error("Ошибка при отправке результата совместимости: " . $e->getMessage());
            }
        }
    }

    /**
     * @throws TelegramSDKException
     */
    private function sendHintMessage($chatId)
    {
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Хо-хо, не могу понять что вы пишете! 😅 Лучше используйте кнопки для взаимодействия со мной.",
            'reply_markup' => json_encode([
                'keyboard' => [
                    [
                        ['text' => 'Начать тест заново', 'callback_data' => 'restart_test'],
                        ['text' => 'Пригласить другого друга', 'switch_inline_query' => "start"],
                    ]
                ],
                'resize_keyboard' => true,
                'one_time_keyboard' => true
            ])
        ]);
    }
}
