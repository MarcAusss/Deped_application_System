<?php

namespace App\Filament\Resources\JobPositions;

use App\Models\JobPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use App\Filament\Resources\JobPositions\Pages;

class JobPositionResource extends Resource
{
    protected static ?string $model = JobPosition::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Recruitment';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('title')
                ->required(),

            Forms\Components\Textarea::make('description')
                ->required(),

            Forms\Components\Toggle::make('is_open')
                ->label('Available for Hiring')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable(),

                Tables\Columns\IconColumn::make('is_open')
                    ->boolean()
                    ->label('Open'),
            ])
            ->actions([
                Tables\Actions\Action::make('toggle')
                    ->icon('heroicon-o-switch-horizontal')
                    ->color('warning')
                    ->action(fn ($record) => $record->update([
                        'is_open' => !$record->is_open,
                    ])),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJobPositions::route('/'),
            'create' => Pages\CreateJobPosition::route('/create'),
            'edit' => Pages\EditJobPosition::route('/{record}/edit'),
        ];
    }
}