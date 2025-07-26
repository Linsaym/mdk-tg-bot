<?php

namespace App\Filament\Resources\TelegramMessageResource\Pages;

use App\Filament\Resources\TelegramMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTelegramMessage extends CreateRecord
{
    protected static string $resource = TelegramMessageResource::class;
}
