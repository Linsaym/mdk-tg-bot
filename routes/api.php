<?php

use App\Http\Controllers\TelegramBotController;

//Проверка домена на открытие
Route::get('/telegram-webhook', fn () => 123);

//Основной вебхук
Route::post('/telegram-webhook', [TelegramBotController::class, 'handleWebhook']);
