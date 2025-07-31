<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TravelPairResource\Pages;
use App\Filament\Resources\TravelPairResource\RelationManagers;
use App\Models\TravelPair;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TravelPairResource extends Resource
{
    protected static ?string $model = TravelPair::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTravelPairs::route('/'),
            'create' => Pages\CreateTravelPair::route('/create'),
            'edit' => Pages\EditTravelPair::route('/{record}/edit'),
        ];
    }
}
