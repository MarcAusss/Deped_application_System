<?php

namespace App\Filament\Evaluator\Resources\Applications\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class EducationRelationManager extends RelationManager
{
    protected static string $relationship = 'educations';

    protected static ?string $title = 'Educational Background';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('level')
                    ->label('Level')
                    ->formatStateUsing(fn (string $state, $record) => $state === "Other's" && filled($record->level_specify)
                        ? $state.' - '.$record->level_specify
                        : $state)
                    ->sortable(),

                Tables\Columns\TextColumn::make('school')
                    ->label('School / Institution')
                    ->searchable(),

                Tables\Columns\TextColumn::make('degree')
                    ->label('Degree / Course')
                    ->searchable(),

                Tables\Columns\TextColumn::make('year_graduated')
                    ->label('Year Graduated')
                    ->sortable(),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('level')
                ->label('Education Level')
                ->options([
                    'Elementary'    => 'Elementary',
                    'High School'   => 'High School',
                    'Vocational'    => 'Vocational',
                    'College'       => 'College',
                    'Post Graduate' => 'Post Graduate',
                    "Other's"       => "Other's",
                ])
                ->live()
                ->required(),

            Forms\Components\TextInput::make('level_specify')
                ->label('Please specify')
                ->maxLength(255)
                ->visible(fn (Get $get) => $get('level') === "Other's")
                ->required(fn (Get $get) => $get('level') === "Other's"),

            Forms\Components\TextInput::make('school')
                ->label('School / Institution')
                ->maxLength(255),

            Forms\Components\TextInput::make('degree')
                ->label('Degree / Course')
                ->maxLength(255),

            Forms\Components\TextInput::make('year_graduated')
                ->label('Year Graduated')
                ->numeric()
                ->minValue(1900)
                ->maxValue(now()->year),
        ]);
    }
}