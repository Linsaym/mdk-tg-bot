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

    public function __construct(Api $telegram)
    {
        $this->telegram = $telegram;
    }

    /**
     * @throws TelegramSDKException
     */
    public function handleWebhook(Request $request)
    {
        config(['session.driver' => 'array']);

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
            $user = TravelUser::firstOrCreate(['telegram_id' => $chatId]);

            if ($text === '/start') {
                $this->handleStartCommand($chatId, $user);
            } elseif (!$user->name) {
                $this->saveUserName($chatId, $user, $text);
            } else {
                // Добавленная обработка случайных сообщений
                $this->sendHintMessage($chatId);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * @throws TelegramSDKException
     */
    private function handleStartCommand($chatId, TravelUser $user)
    {
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

        // Если подписан и имя есть — начинаем тест
        $this->sendFirstQuestion($chatId);
    }

    /**
     * @throws TelegramSDKException
     */
    private function askForSubscription($chatId)
    {
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Ой! Похоже, вы ещё не подписаны на канал Ozon Travel. Подпишитесь, чтобы открыть доступ к тесту!",
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
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Перед тем, как отправиться в путешествие, расскажите, как вас зовут!"
        ]);
    }

    private function checkSubscription($chatId)
    {
        // Временная заглушка: всегда считаем, что пользователь подписан
        return true;

//        try {
//            $response = $this->telegram->getChatMember([
//                'chat_id' => '@ozontravel_official',
//                'user_id' => $chatId
//            ]);
//            return in_array($response->status, ['member', 'administrator', 'creator']);
//        } catch (\Exception $e) {
//            Log::error("Ошибка проверки подписки: " . $e->getMessage());
//            return false;
//        }
    }

    /**
     * @throws TelegramSDKException
     */
    private function saveUserName($chatId, TravelUser $user, $name)
    {
        $user->update(['name' => $name]);
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Приятно познакомиться, $name! Что нужно сделать:\n1. Пройдите тест.\n2. Поделитесь ссылкой с друзьями.\n3. Узнаете, подходите ли для совместных поездок!",
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
            'text' => $question->text,
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

        if ($data === 'check_subscription') {
            $this->handleSubscriptionCheck($chatId, $user);
        } elseif ($data === 'start_test' || $data === 'restart_test') {
            $this->sendFirstQuestion($chatId);
        } elseif (str_starts_with($data, 'answer_')) {
            // Убираем кнопки в сообщении, на котором была нажата кнопка
            $this->removeInlineButtons($chatId, $messageId);
            $this->handleAnswer($chatId, $data, $user);
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

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Тест завершён! Пригласите друга, чтобы узнать совместимость:",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [ // Первая строка кнопок
                        [
                            'text' => 'Позвать друга',
                            'switch_inline_query' => "start={$user->telegram_id}"
                        ]
                    ],
                    [ // Вторая строка кнопок
                        [
                            'text' => 'Пройти тест заново',
                            'callback_data' => 'restart_test'
                        ]
                    ]
                ]
            ])
        ]);
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
                'keyboard' => [[
                    ['text' => 'Начать тест заново', 'callback_data' => 'restart_test']
                ]],
                'resize_keyboard' => true,
                'one_time_keyboard' => true
            ])
        ]);
    }
}
