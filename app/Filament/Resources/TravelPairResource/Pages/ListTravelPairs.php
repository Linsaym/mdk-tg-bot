<?php

namespace App\Filament\Resources\TravelPairResource\Pages;

use App\Filament\Resources\TravelPairResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTravelPairs extends ListRecords
{
    protected static string $resource = TravelPairResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
