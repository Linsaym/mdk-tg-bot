<?php

use App\Http\Controllers\TelegramBotController;
use App\Http\Controllers\TestTelegramBotController;

Route::get('/telegram-webhook', fn () => 123);

Route::post('/telegram-webhook', [TelegramBotController::class, 'handleWebhook']);

//Для тестового телеграм бота
Route::post('/test-telegram-webhook', [TestTelegramBotController::class, 'handleWebhook']);
