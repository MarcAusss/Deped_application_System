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

                Tables\Columns\TextColumn::make('training_date')
                    ->label('Start')
                    ->date('F Y')
                    ->placeholder('Not provided')
                    ->sortable(),

                Tables\Columns\TextColumn::make('training_end_date')
                    ->label('End')
                    ->date('F Y')
                    ->placeholder('Not provided')
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

            Forms\Components\TextInput::make('training_date')
                ->label('Start of Training')
                ->type('month')
                ->formatStateUsing(
                    fn ($state): ?string => filled($state)
                        ? \Carbon\Carbon::parse($state)->format('Y-m')
                        : null
                )
                ->dehydrateStateUsing(
                    fn ($state): ?string => filled($state)
                        ? $state.'-01'
                        : null
                )
                ->rules(['nullable', 'date_format:Y-m'])
                ->extraInputAttributes([
                    'max' => now()->format('Y-m'),
                ]),

            Forms\Components\TextInput::make('training_end_date')
                ->label('End of Training')
                ->type('month')
                ->formatStateUsing(
                    fn ($state): ?string => filled($state)
                        ? \Carbon\Carbon::parse($state)->format('Y-m')
                        : null
                )
                ->dehydrateStateUsing(
                    fn ($state): ?string => filled($state)
                        ? $state.'-01'
                        : null
                )
                ->rules(['nullable', 'date_format:Y-m'])
                ->extraInputAttributes([
                    'max' => now()->format('Y-m'),
                ]),
        ]);
    }
}
