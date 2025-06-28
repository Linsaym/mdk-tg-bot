<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramBotController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // Получаем данные от Telegram
        $update = Telegram::commandsHandler(true); // Автоматически вызывает команды

        // Или обрабатываем вручную
        $message = $update->getMessage();
        $chatId = $message->getChat()->getId();
        $text = $message->getText();

        // Отправляем ответ
        if ($text === '/start') {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => 'Привет! Я бот на Laravel!',
            ]);
        }

        return response()->json(['status' => 'ok']);
    }
}
