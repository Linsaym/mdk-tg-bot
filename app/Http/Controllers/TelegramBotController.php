<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\TravelUser;
use App\Models\Question;
use Exception;
use Telegram\Bot\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\FileUpload\InputFile;

class TelegramBotController extends Controller
{
    private Api $telegram;


    public array $greetings;

    // Общая инструкция
    public string $instructions = "\n\nЧто нужно сделать:\n"
    . "1. Сначала самостоятельно пройдите тест из 10 вопросов.\n"
    . "2. Поделитесь ссылкой на тест с друзьями.\n"
    . "3. После прохождения вы узнаете, подходите ли вы для совместных поездок или ваши предпочтения слишком разные по вайбу.";


    public function __construct(Api $telegram)
    {
        $this->telegram = $telegram;
        $this->greetings = config('telegram_messages.greetings');
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
            Log::info('msg', [$message]);
            switch (true) {
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
                    Log::info('msg', [$currentQuestion]);
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

                case $text === "Начать тест заново":
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Напиши `/start 123` (вместо 123 код того кто вас пригласил)"
                    ]);
                    break;

                case str_starts_with($text, 'Я'):
                    $this->saveUserName($chatId, $user, $text_split[1]);
                    break;

                case !$user->name:
                    $this->askForName($chatId);
                    break;

                default:
                    $this->sendHintMessage($chatId);
                    break;
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

        // Если имя не указанно
        if (!$user->name) {
            $this->askForName($chatId);
            return;
        }

        //Спрашиваем подписку
        $this->askForSubscription($chatId);
        return;


//        $this->telegram->sendMessage([
//            'chat_id' => $chatId,
//            'text' => $this->getRandomGreetingWithInstructions()
//        ]);
//        // Если подписан и имя есть — начинаем тест
//        $this->sendFirstQuestion($chatId);
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
        }
    }

    /**
     * @throws TelegramSDKException
     */
    private function askForSubscription($chatId)
    {
        $messages = config('telegram_messages.ask_for_subscription');

        $randomMessage = $messages[array_rand($messages)];

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
        $nameRequestMessages = config('telegram_messages.name_request_messages');
        $welcomeMessages = config('telegram_messages.welcome_messages');
        $nameRequestMessage = $nameRequestMessages[array_rand($nameRequestMessages)];
        $welcomeMessage = $welcomeMessages[array_rand($welcomeMessages)];


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
        $user->update(['name' => $name]);
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
            'text' => $this->getRandomGreetingWithInstructions(),
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
            Log::error($e->getMessage());
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
        $isSubscribed = $this->checkSubscription($chatId);

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
