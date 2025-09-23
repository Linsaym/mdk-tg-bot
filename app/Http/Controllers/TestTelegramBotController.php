<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\TravelUser;
use App\Models\Question;
use App\Repositories\TelegramMessageRepository;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;
use Telegram\Bot\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Exceptions\TelegramSDKException;

class TestTelegramBotController extends Controller
{
    private Api $telegram;

    private TelegramMessageRepository $messageRepository;

    // Общая инструкция
    public string $instructions = "\nЧто нужно сделать:\n"
    . "1. Сначала самостоятельно пройдите тест из 10 вопросов.\n"
    . "2. Поделитесь ссылкой на тест с друзьями.\n"
    . "3. После прохождения вы узнаете, подходите ли вы для совместных поездок или ваши предпочтения слишком разные по вайбу.";


    /**
     * @throws TelegramSDKException
     */
    public function __construct(Api $telegram, TelegramMessageRepository $messageRepository)
    {
        // Используем токен тестового бота
        $testBotToken = config('telegram.bots.test.token');
        $this->telegram = new Api($testBotToken);

        $this->messageRepository = $messageRepository;
    }

    /**
     * @throws TelegramSDKException
     */
    public function handleWebhook(Request $request)
    {
        // Временное переключение на тестовую БД
        config(['database.default' => 'mysql_test']);

        $update = $this->telegram->getWebhookUpdate();

        $chatId = $update->getChat()?->id;
        $message = $update->getMessage();
        $callbackQuery = $update->getCallbackQuery();


        // Обработка callback-кнопок
        if ($callbackQuery) {
            $this->handleCallbackQuery($callbackQuery);
            return response()->json(['status' => 'ok']);
        }


        // Обработка текстовых сообщений
        if ($text = $message->text) {
            $text_split = explode(' ', $text);
            $user = TravelUser::firstOrCreate(['telegram_id' => $chatId]);
            switch (true) {
                case $text === "/winner-aB4":
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "🥳 Поздравляем! Вы стали победителем розыгрыша Ozon Travel Vibe.\n
Ваш приз — 50&#160;000 Ozon-баллов. Вот ваш уникальный код: XXXXX-XXXXX.\n
Информация об активации промокода и начислении баллов — читайте <a href='https://ozon.ru/t/OM4oXCz'>здесь</a>.\n
А&#160;если возникли вопросы, пишите в&#160;наш чат поддержки: @Ozontravel1bot\n
Спасибо, что участвуете вместе с&#160;нами! 💙",
                        'parse_mode' => 'HTML',
                    ]);
                    break;
                case $text === "/start_lottery-aB4":
                    $this->sendLotteryNotification();
                    break;
                case $text === "/remind-aB4":
                    $this->sendReminderNotification();
                    break;
                case $text === "/winers-aB4":
                    $this->sendWinnersNotification();
                    break;
                case $text === "/code":
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Ваш код: `$chatId`"
                    ]);
                    return response()->json(['status' => 'ok']);

                case str_starts_with($text, '/start'):
                    $this->handleStartCommand($chatId, $user, $text);
                    break;

                case $text === "Пригласить другого друга":
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Просто отправь ему свой код: `$chatId`"
                    ]);
                    break;
                case $text === "Про меня":
                case $text === "Не про меня":
                case $text === "По настроению":
                    $currentQuestion = $this->getCurrentQuestion($user);
                    if ($currentQuestion) {
                        $answer = Answer::where('question_id', $currentQuestion->id)
                            ->where('text', $text)
                            ->first();

                        if ($answer) {
                            $this->handleTextAnswer($chatId, $user, $currentQuestion, $answer);
                        }
                    } else {
                        $this->sendHintMessage($chatId);
                    }
                    break;

                case str_starts_with($text, 'Я'):
                    $this->saveUserName($chatId, $user, $text_split[1]);
                    break;

                case !$user->name:
                case $text === "Начать тест заново":
                    $this->askForName($chatId);
                    break;

                default:
                    $this->sendHintMessage($chatId);
                    break;
            }
        }

        // Возвращаем настройки обратно (опционально)
        config(['database.default' => 'mysql']);

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

        // Если имя не указанно
        if (!$user->name) {
            $this->askForName($chatId);
            return;
        }

        $this->askForSubscription($chatId);
    }

    public function sendNotifications(Request $request)
    {
        $type = $request->input('type', 'lottery');
        $winners = $request->input('winners');

        $output = new BufferedOutput();

        try {
            $exitCode = Artisan::call('notification:send', [
                'type' => $type,
                '--winners' => $winners
            ], $output);

            $result = $output->fetch();

            if ($exitCode === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Рассылка успешно запущена',
                    'output' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при запуске рассылки',
                    'output' => $result
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Исключение при выполнении команды: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Запуск рассылки о розыгрыше
     */
    public function sendLotteryNotification()
    {
        return $this->sendNotifications(new Request(['type' => 'lottery']));
    }

    /**
     * Запуск рассылки о победителях
     */
    public function sendWinnersNotification()
    {
        return $this->sendNotifications(new Request([
            'type' => 'winners',
            'winners' => ""
        ]));
    }

    /**
     * Запуск напоминания
     */
    public function sendReminderNotification()
    {
        return $this->sendNotifications(new Request(['type' => 'reminder']));
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
            } else {
                $this->telegram->sendMessage(
                    [
                        'chat_id' => $user->telegram_id,
                        'text' => 'Самого себя пригласить нельзя, лучше отправьте ваш код или ссылку другу😅'
                    ]
                );
            }
        } else {
            try {
                $this->telegram->sendMessage(
                    [
                        'chat_id' => $user->telegram_id,
                        'text' => 'Привет! Если ваш друг уже прошел тест, и у вас есть код друга, просто введите /start 123 (замените 123 на его код), и бот подключит вас к его путешествию.'
                    ]
                );
                $this->telegram->sendMessage(
                    [
                        'chat_id' => $user->telegram_id,
                        'text' => 'Если же вы с друзьями еще не проходили тест, то давайте приступим — будет интересно!'
                    ]
                );
            } catch (TelegramSDKException $e) {
                return;
            }
        }
    }

    /**
     * @throws TelegramSDKException
     */
    private function askForSubscription($chatId)
    {
        $randomMessage = $this->messageRepository->getRandomMessageFromGroup('ask_for_subscription');;

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $randomMessage,
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => 'Подписаться', 'url' => 'https://t.me/+sUletwbFVeA2OWYy']],
                    [['text' => 'Я подписан', 'callback_data' => 'check_subscription']]
                ]
            ])
        ]);
    }

    /**
     * @throws TelegramSDKException
     */
    private function askForName($chatId)
    {
        $nameRequestMessage = $this->messageRepository->getRandomMessageFromGroup('name_request_messages');
        $welcomeMessage = $this->messageRepository->getRandomMessageFromGroup('welcome_messages');


        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $this->checkSubscription($chatId) ? $welcomeMessage : $nameRequestMessage
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
        } catch (Exception $e) {
            Log::error("Ошибка проверки подписки: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @throws TelegramSDKException
     */
    private function saveUserName($chatId, TravelUser $user, $name)
    {
        $user->name = $name;
        $user->save();
        $this->askForSubscription($chatId);
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
    private function sendStartTestButton($chatId)
    {
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $this->messageRepository->getRandomMessageFromGroup('greetings'),
            'reply_markup' => json_encode([
                'inline_keyboard' => [[['text' => 'Начать тест', 'callback_data' => 'start_test']]]
            ])
        ]);
    }

    /**
     * @throws TelegramSDKException
     */
    private function sendQuestion($chatId, Question $question)
    {
        //$this->sendQuestionGif($chatId, $question);

        $keyboard = $question->answers->map(function ($answer) use ($question) {
            return [['text' => $answer->text, 'callback_data' => "answer_{$question->id}_{$answer->id}"]];
        })->toArray();

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "❓ Вопрос " . $question->question_number . ": " . $question->text,
            'reply_markup' => json_encode([
                'keyboard' => $keyboard,
                'resize_keyboard' => true,
                'one_time_keyboard' => true
            ])
        ]);
    }

    /**
     * @throws TelegramSDKException
     * @throws Exception
     */
    private function sendQuestionGif($chatId, Question $question, string $text = '')
    {
        if (!$question->telegram_file_id) {
            throw new Exception("Telegram file_id not found for question {$question->id}");
        }

        try {
            $this->telegram->sendAnimation([
                'chat_id' => $chatId,
                'animation' => "{$question->telegram_file_id}",
                'caption' => ""
            ]);
        } catch (Exception $e) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'не получилось отправить гифку('
            ]);
        }
    }

    private function getCurrentQuestion(TravelUser $user): ?Question
    {
        $answers = $user->test_answers ? json_decode($user->test_answers, true) : [];

        if (empty($answers)) {
            return Question::first();
        }

        $lastQuestionId = max(array_keys($answers));
        return Question::where('id', '>', $lastQuestionId)->first();
    }

    /**
     * @throws TelegramSDKException
     */
    private function handleTextAnswer($chatId, TravelUser $user, Question $question, Answer $answer)
    {
        // Сохраняем ответ
        $answers = $user->test_answers ? json_decode($user->test_answers, true) : [];
        $answers[$question->id] = $answer->id;
        $user->update(['test_answers' => json_encode($answers)]);

        // Отправляем реакцию
        if ($answer->reaction) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $answer->reaction
            ]);
        }

        // Следующий вопрос
        $nextQuestion = Question::where('id', '>', $question->id)->first();
        if ($nextQuestion) {
            $this->sendQuestion($chatId, $nextQuestion);
        } else {
            $this->completeTest($chatId, $user);
        }
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
            case 'participate':
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "
                    ✨Чтобы ваш билет удачи остался активным — <a href='https://ozon.ru/t/OM4oXCz'>подтвердите участие</a>! Жмите «Принять» и&#160;оставайтесь в&#160;игре за&#160;100&#160;000 Ozon-баллов на&#160;двоих — для вас и&#160;вашей тревел-половинки.
                    ",
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [
                                [
                                    'text' => '✅ Принять условия',
                                    'callback_data' => 'accept_terms'
                                ],
                            ],
                        ]
                    ])
                ]);
                break;
            case 'accept_terms':
                if ($user->participate_in_lottery) {
                    $refLink = "https://t.me/ozon_travel_vibe_bot?start=" . $chatId;
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Вы уже участвуете в розыгрыше, а если хотите увеличить шансы — пригласите друзей 😉",
                        'reply_markup' => json_encode([
                            'inline_keyboard' => [
                                [
                                    [
                                        'text' => 'Поделиться с друзьями',
                                        'url' => "https://t.me/share/url?text=" . rawurlencode(
                                                "🌴Совпадаете по отпускному вайбу? Пройдите тест c другом и участвуйте в розыгрыше 100 000 Ozon баллов на двоих! 🎉"
                                            ) . "&url=" . urlencode($refLink)
                                    ]
                                ]
                            ]
                        ])
                    ]);
                } else {
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Последний шаг: <a href='https://mdk-bots.ru/verification?code=$chatId'>пройдите капчу</a> — и вы в конкурсе! 🎉",
                        'parse_mode' => 'HTML',
                    ]);
//                    $user->update(['participate_in_lottery' => true, 'test_answers' => null]);
//                    $this->telegram->sendMessage([
//                        'chat_id' => $chatId,
//                        'text' => "Вы участвуете в розыгрыше! 🎉"
//                    ]);
                }
                break;
            case 'skip_lottery':
                $user->update(['participate_in_lottery' => false, 'test_answers' => null]);
                $this->sendFirstQuestion($chatId);
                //$this->removeInlineButtons($chatId, $messageId);
                break;
            case 'check_subscription':
                $this->handleSubscriptionCheck($chatId, $user);
                break;

            case 'start_test':
                $user->update(['test_answers' => null]);
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
        } catch (Exception $e) {
            Log::error("Ошибка при удалении кнопок: " . $e->getMessage());
        }
    }

    /**
     * @throws TelegramSDKException
     */
    private function handleSubscriptionCheck($chatId, TravelUser $user)
    {
        //$isSubscribed = $this->checkSubscription($chatId);
        //В тестовом боте юзер всегда подписан
        $isSubscribed = true;

        if ($isSubscribed) {
            $user->update(['is_subscribed' => true]);

            if (!$user->name) {
                $this->askForName($chatId);
            } else {
                $this->sendStartTestButton($chatId);
            }
        } else {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Вы всё ещё не подписаны. Пожалуйста, подпишитесь на канал и нажмите 'Я подписался!' снова."
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
        $refLink = "https://t.me/ozon_travel_vibe_bot?start=" . $user->telegram_id;
        $randomMsg = $this->messageRepository->getRandomMessageFromGroup('complete_test_message');;

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $randomMsg,
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => 'Пройти тест заново', 'callback_data' => 'restart_test']],
                    [
                        [
                            'text' => 'Поделиться с друзьями',
                            'url' => "https://t.me/share/url?text=" . rawurlencode(
                                    "🌴Совпадаете по отпускному вайбу? Пройдите тест c другом и участвуйте в розыгрыше 100 000 Ozon баллов на двоих! 🎉 "
                                ) . "&url=" . urlencode($refLink)
                        ]
                    ]
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
            } catch (Exception $e) {
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

            $message = "🎉 Результат совместимости с $partnerName!\n";
            $message .= "{$compatibilityText}\n";
            $message .= "Вы можете пройти тест с другими друзьями, чтобы сравнить результаты!";

            $refLink = "https://t.me/ozon_travel_vibe_bot?start=" . $chatId;

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            [
                                'text' => 'Пригласить еще друзей',
                                'url' => "https://t.me/share/url?text=" . rawurlencode(
                                        "Пройди тест и узнаем, совпадаем ли мы по отпускному вайбу! 🌴 "
                                    ) . "&url=" . urlencode($refLink)
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

    /**
     * @throws TelegramSDKException
     */
    public function verifyCode(Request $request)
    {
        config(['database.default' => 'mysql_test']);
        $code = $request->input('code');
        $user = TravelUser::where('telegram_id', '743206490')->firstOrFail();
        $user->update(['participate_in_lottery' => true]);
        $this->telegram->sendMessage([
            'chat_id' => $code,
            'text' => "Поздравляем! \nВы участвуете в конкурсе. Удачи!🍀",
            'parse_mode' => 'HTML',
        ]);
        return view('captcha-success');
        $request->validate([
            'code' => 'required|string',
            'g-recaptcha-response' => 'required'
        ]);

        $code = $request->input('code');

        // Проверка reCAPTCHA
        $recaptchaResponse = $request->input('g-recaptcha-response');
        $secretKey = '6Ld7S9ErAAAAAB6Hn4ISaDlUSPWA12kfG0Hu3YGg';

        $response = file_get_contents(
            "https://www.google.com/recaptcha/api/siteverify?secret=" .
            $secretKey . "&response=" . $recaptchaResponse
        );

        $responseKeys = json_decode($response, true);

        Log::info('Код, ключи успеха, секрет код', [$code, $responseKeys["success"], $responseKeys]);

        if (intval($responseKeys["success"]) !== 1) {
            return back()->with('error', 'Ошибка проверки reCAPTCHA');
        }

        if (!$code) {
            return back()->with('error', 'Ошибка, попробуйте снова чуть позже');
        }

        $user = $user = TravelUser::where('telegram_id', '743206490')->firstOrFail();

        $user->update(['participate_in_lottery' => true]);

        $this->telegram->sendMessage([
            'chat_id' => $code,
            'text' => "Поздравляем! 🎊 \nВы успешно прошли капчу и теперь участвуете в конкурсе. Удачи! 🍀",
            'parse_mode' => 'HTML',
        ]);

        return view('captcha-success');
    }
}
