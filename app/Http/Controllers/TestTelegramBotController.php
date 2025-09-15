<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\ContestParticipant;
use App\Models\TravelUser;
use App\Models\Question;
use App\Repositories\TelegramMessageRepository;
use Exception;
use Telegram\Bot\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Exceptions\TelegramSDKException;

class TestTelegramBotController extends Controller
{
    private Api $telegram;

    private TelegramMessageRepository $messageRepository;

    // Общая инструкция
    public string $instructions = "\n\nЧто нужно сделать:\n"
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
            $user = ContestParticipant::firstOrCreate(['telegram_id' => $chatId]);

            switch (true) {
                case $text === "/code":
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Ваш код: `$chatId`"
                    ]);
                    break;

                case str_starts_with($text, '/start'):
                    $this->handleStartCommand($chatId, $user);
                    break;

                case str_starts_with($text, 'Я'):
                    $this->saveUserName($chatId, $user, $text_split[1]);
                    break;

                default:
                    $this->sendHintMessage($chatId);
                    break;
            }
        }

        // Возвращаем настройки обратно
        config(['database.default' => 'mysql']);

        return response()->json(['status' => 'ok']);
    }

    private function handleStartCommand($chatId, $user)
    {
        // Если пользователь уже принял условия
        if ($user->accepted_terms) {
            $this->sendWelcomeBackMessage($chatId);
        } else {
            // Отправляем сообщение с кнопкой принятия условий
            $this->sendTermsAcceptanceMessage($chatId);
        }
    }

    private function sendTermsAcceptanceMessage($chatId)
    {
        $welcomeTexts = [
            "✨ Перед тем как продолжить участие, примите условия конкурса. Нажмите кнопку «Принять», чтобы подтвердить участие и сохранить свой шанс на 50 000 Ozon-баллов.",
            "✨ Чтобы ваш билет удачи остался активным — подтвердите участие! Жмите «Принять» и оставайтесь в игре за 100 000 Ozon-баллов на двоих — для вас и вашей тревел-половинки."
        ];

        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '✅ Принять условия конкурса',
                        'callback_data' => 'accept_terms'
                    ]
                ]
            ]
        ];

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $welcomeTexts[array_rand($welcomeTexts)],
            'reply_markup' => json_encode($keyboard)
        ]);
    }

    private function handleCallbackQuery($callbackQuery)
    {
        $chatId = $callbackQuery->getMessage()->getChat()->id;
        $data = $callbackQuery->getData();

        switch ($data) {
            case 'accept_terms':
                $this->acceptTerms($chatId);
                break;
        }

        // Ответ на callback query
        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $callbackQuery->getId()
        ]);
    }

    private function acceptTerms($chatId)
    {
        $user = ContestParticipant::where('telegram_id', $chatId)->first();

        if ($user) {
            $user->accepted_terms = true;
            $user->accepted_terms_at = now();
            $user->save();

            // Отправляем приветственное сообщение
            $welcomeMessages = [
                "🎉 Добро пожаловать в розыгрыш Ozon Travel Vibe!\n\nС 1 по 30 сентября мы проводим сразу два розыгрыша по 500 000 Ozon-баллов. В каждом розыгрыше мы случайным образом выбираем 5 пар (10 победителей), и каждый из них получает по 50 000 баллов.\n\nПриглашайте друзей, проходите тесты и увеличивайте свои шансы! 🚀",
                "✨ А вы готовы поймать удачу? Ozon Travel Vibe разыгрывает 1 000 000 Ozon-баллов целый месяц до 7 октября!\n\nКаждые две недели выбираем 5 пар участников. Каждому победителю — по 50 000 баллов.\n\nУдвойте. Утройте! Учетверите! Шансы на победу — зовите участвовать всех, кто не меньше вас заслужил отдых!"
            ];

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $welcomeMessages[array_rand($welcomeMessages)]
            ]);

            // Напоминание о приглашении друзей
            $this->sendInviteReminder($chatId);
        }
    }

    private function sendInviteReminder($chatId)
    {
        $reminderTexts = [
            "🔥 Напоминаем: чтобы участвовать в розыгрыше и увеличить шансы, пригласите ещё друзей в Ozon Travel Vibe! Каждый новый друг — это дополнительный шанс стать победителем и получить 50 000 Ozon-баллов. 🚀",
            "🔥 Больше друзей — больше шансов! Не упустите возможность: приглашайте знакомых в Ozon Travel Vibe и расширяйте свои шансы на выигрыш 100 000 Ozon-баллов на двоих. 🚀"
        ];

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $reminderTexts[array_rand($reminderTexts)]
        ]);
    }

    private function sendWelcomeBackMessage($chatId)
    {
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "С возвращением! Вы уже участвуете в розыгрыше. 🎉"
        ]);
    }

    private function sendHintMessage($chatId)
    {
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Используйте команды бота для взаимодействия. Для начала работы используйте /start"
        ]);
    }

// Метод для выбора победителей (запускается по расписанию)
    public function selectWinners()
    {
        $currentContest = Contest::where('is_active', true)->first();

        if (!$currentContest) {
            return;
        }

        // Выбираем 10 случайных участников, принявших условия
        $winners = ContestParticipant::where('accepted_terms', true)
            ->inRandomOrder()
            ->limit(10)
            ->get();

        foreach ($winners as $winner) {
            // Генерируем уникальный код
            $prizeCode = strtoupper(substr(md5(uniqid()), 0, 10));

            // Сохраняем победителя
            Winner::create([
                'contest_id' => $currentContest->id,
                'participant_id' => $winner->id,
                'prize_code' => $prizeCode,
                'prize_amount' => 50000
            ]);

            // Отправляем сообщение победителю
            $winnerMessages = [
                "🥳 Поздравляем! Вы стали победителем розыгрыша Ozon Travel Vibe.\n\nВаш приз — 50 000 Ozon-баллов. Вот ваш уникальный код: $prizeCode.\n\nСпасибо, что участвуете вместе с нами! 💙",
                "Ура! Вам улыбнулась удача в Ozon Travel Vibe. 🎉\n\nВы выиграли 50 000 Ozon-баллов! Ваш призовой код: $prizeCode.\n\nЖелаем приятных путешествий и новых побед! 💙"
            ];

            $this->telegram->sendMessage([
                'chat_id' => $winner->telegram_id,
                'text' => $winnerMessages[array_rand($winnerMessages)]
            ]);
        }

        // Отправляем уведомление всем участникам
        $this->sendWinnerAnnouncement($winners);
    }

    private function sendWinnerAnnouncement($winners)
    {
        $winnerList = "";
        foreach ($winners as $winner) {
            $winnerList .= "@{$winner->username}\n";
        }

        $announcementTexts = [
            "Итоги розыгрыша Ozon Travel Vibe подведены! 🎉\n\nПоздравляем наших победителей:\n$winnerList\nНе расстраивайтесь, если удача пока не улыбнулась — впереди ещё розыгрыши. Оставайтесь подписанными на канал, чтобы снова испытать удачу! 💙",
            "🎉 Розыгрыш завершён, и у нас есть счастливчики!\n\n$winnerList\nКаждый получил по 50 000 Ozon-баллов. А уже скоро стартует новый розыгрыш — оставайтесь в Ozon Travel Vibe и ловите шанс на победу снова! 💙"
        ];

        // Получаем всех участников
        $participants = ContestParticipant::where('accepted_terms', true)->get();

        foreach ($participants as $participant) {
            $this->telegram->sendMessage([
                'chat_id' => $participant->telegram_id,
                'text' => $announcementTexts[array_rand($announcementTexts)]
            ]);
        }
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
        $this->sendQuestionGif($chatId, $question);

        $keyboard = $question->answers->map(function ($answer) use ($question) {
            return [['text' => $answer->text, 'callback_data' => "answer_{$question->id}_{$answer->id}"]];
        })->toArray();

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "❓ Вопрос " . $question->id . ": " . $question->text,
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
                                    "Пройди тест и узнаем, совпадаем ли мы по отпускному вайбу! 🌴 "
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

            $message = "🎉 Результат совместимости с $partnerName!\n\n";
            $message .= "{$compatibilityText}\n\n";
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
}
