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

class EligibilityRelationManager extends RelationManager
{
    protected static string $relationship = 'eligibilities';

    protected static ?string $title = 'Eligibilities / Licenses';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('license_name')
                    ->label('License / Eligibility')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating')
                    ->sortable(),

                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Valid Until')
                    ->date('M d, Y')
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
            Forms\Components\TextInput::make('license_name')
                ->label('License / Eligibility Name')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('rating')
                ->label('Rating')
                ->maxLength(100),

            Forms\Components\DatePicker::make('valid_until')
                ->label('Valid Until')
                ->nullable(),
        ])->columns(2);
    }
}