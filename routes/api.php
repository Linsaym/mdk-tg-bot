<?php

use App\Http\Controllers\TelegramBotController;

Route::post('/telegram-webhook', [TelegramBotController::class, 'handleWebhook']);
Route::get('/telegram-webhook', fn()=>123);
