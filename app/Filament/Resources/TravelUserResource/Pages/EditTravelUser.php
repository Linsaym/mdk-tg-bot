<?php

namespace App\Filament\Resources\TravelUserResource\Pages;

use App\Filament\Resources\TravelUserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTravelUser extends EditRecord
{
    protected static string $resource = TravelUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
