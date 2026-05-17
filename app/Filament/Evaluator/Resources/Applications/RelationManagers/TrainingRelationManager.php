<?php

namespace App\Filament\Evaluator\Resources\Applications\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class TrainingRelationManager extends RelationManager
{
    protected static string $relationship = 'trainings';

    protected static ?string $title = 'Trainings & Seminars';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Training Title')
                    ->searchable(),

                Tables\Columns\TextColumn::make('hours')
                    ->label('Hours')
                    ->suffix(' hrs')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('title')
                ->label('Training / Seminar Title')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Forms\Components\TextInput::make('hours')
                ->label('Number of Hours')
                ->numeric()
                ->minValue(1)
                ->suffix('hrs'),

            Forms\Components\Textarea::make('details')
                ->label('Details / Description')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }
}