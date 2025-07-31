<?php

namespace App\Http\Controllers;

use App\Models\TelegramMessage;

class BotMessagesController extends Controller
{
    public function index()
    {
        $groups = TelegramMessage::select('group')
            ->distinct()
            ->get()
            ->mapWithKeys(function ($item) {
                $messages = TelegramMessage::where('group', $item->group)
                    ->orderBy('order')
                    ->get();
                return [$item->group => $messages];
            });
    }
}