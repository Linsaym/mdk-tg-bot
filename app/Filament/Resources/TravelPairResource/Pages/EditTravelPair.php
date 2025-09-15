<?php

namespace App\Filament\Resources\TravelPairResource\Pages;

use App\Filament\Resources\TravelPairResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTravelPair extends EditRecord
{
    protected static string $resource = TravelPairResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
