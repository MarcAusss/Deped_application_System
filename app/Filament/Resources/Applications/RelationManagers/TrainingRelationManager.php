<?php

namespace App\Filament\Resources\Applications\RelationManagers;

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
                    ->date('F d, Y')
                    ->placeholder('Not provided')
                    ->sortable(),

                Tables\Columns\TextColumn::make('training_end_date')
                    ->label('End')
                    ->date('F d, Y')
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

            Forms\Components\DatePicker::make('training_date')
                ->label('Start of Training')
                ->displayFormat('F d, Y')
                ->maxDate(now()),

            Forms\Components\DatePicker::make('training_end_date')
                ->label('End of Training')
                ->displayFormat('F d, Y')
                ->rule(fn (callable $get) => function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                    $start = $get('training_date');

                    if (filled($start) && filled($value) && $value < $start) {
                        $fail('The End of Training must be on or after the Start of Training.');
                    }
                }),
        ]);
    }
}
