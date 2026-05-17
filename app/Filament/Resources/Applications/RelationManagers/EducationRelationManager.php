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
            Forms\Components\Select::make('level')
                ->label('Education Level')
                ->options([
                    'Elementary'    => 'Elementary',
                    'High School'   => 'High School',
                    'Vocational'    => 'Vocational',
                    'College'       => 'College',
                    'Post Graduate' => 'Post Graduate',
                ])
                ->required(),

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