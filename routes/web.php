<?php

use App\Http\Controllers\TestTelegramBotController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;


Route::view('/', 'welcome')->name('home');
Route::get('/verification', [TestTelegramBotController::class, 'showForm'])->name('verification.form');
Route::post('/verify-code', [TestTelegramBotController::class, 'verifyCode'])->name('verify.code');

Route::middleware(['auth'])->group(function () {
    Route::view('dashboard-x4', 'dashboard')->name('dashboard');

    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    Volt::route('bot/questions.blade', 'bots.questions')->name('questions');
    Volt::route('/bot/bot-messages', 'bots.bot-messages.index')->name('bot_messages');
});

require __DIR__ . '/auth.php';
