<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TravelUserResource\Pages;
use App\Filament\Resources\TravelUserResource\RelationManagers;
use App\Models\TravelUser;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TravelUserResource extends Resource
{
    protected static ?string $model = TravelUser::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('telegram_id')
                    ->tel()
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('name'),
                Forms\Components\Textarea::make('test_answers')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_subscribed')
                    ->required(),
                Forms\Components\TextInput::make('invited_by')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('telegram_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_subscribed')
                    ->boolean(),
                Tables\Columns\TextColumn::make('invited_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListTravelUsers::route('/'),
            'create' => Pages\CreateTravelUser::route('/create'),
            'edit' => Pages\EditTravelUser::route('/{record}/edit'),
        ];
    }
}
