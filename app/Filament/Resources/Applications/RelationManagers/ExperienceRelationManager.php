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

class ExperienceRelationManager extends RelationManager
{
    protected static string $relationship = 'experiences';

    protected static ?string $title = 'Work Experience';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Job Title')
                    ->searchable(),

                Tables\Columns\TextColumn::make('company')
                    ->label('Company')
                    ->searchable(),

                Tables\Columns\TextColumn::make('years_months')
                    ->label('Duration'),
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
                ->label('Job Title')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('company')
                ->label('Company / Organization')
                ->maxLength(255),

            Forms\Components\TextInput::make('years_months')
                ->label('Duration (e.g. 2 years 3 months)')
                ->maxLength(100),

            Forms\Components\Textarea::make('details')
                ->label('Key Responsibilities / Details')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }
}