<?php

use App\Http\Controllers\TelegramBotController;
use App\Http\Controllers\TestTelegramBotController;

//Проверка домена на открытие
Route::get('/telegram-webhook', fn () => 123);

//Основной вебхук
Route::post('/telegram-webhook', [TelegramBotController::class, 'handleWebhook']);

//Для тестового телеграм бота
Route::post('/test-telegram-webhook', [TestTelegramBotController::class, 'handleWebhook']);
