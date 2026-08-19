<?php

namespace App\Filament\Evaluator\Resources\Applications\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
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
                    ->formatStateUsing(fn (?string $state, $record) => in_array($state, ['RA1080', "Other's"], true) && filled($record->license_specify)
                        ? $state.' - '.$record->license_specify
                        : $state)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating')
                    ->sortable(),

                Tables\Columns\TextColumn::make('date_issued')
                    ->label('Date Issued')
                    ->date('M d, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Valid Until')
                    ->getStateUsing(fn ($record) => $record->never_expires
                        ? 'Never Expires'
                        : (filled($record->valid_until) ? \Carbon\Carbon::parse($record->valid_until)->format('M d, Y') : null))
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
            Forms\Components\Select::make('license_name')
                ->label('License / Eligibility Name')
                ->options([
                    'CS Sub-Professional' => 'CS Sub-Professional',
                    'CSCS Professional' => 'CSCS Professional',
                    'RA1080' => 'RA1080',
                    "Other's" => "Other's",
                ])
                ->live()
                ->required(),

            Forms\Components\TextInput::make('license_specify')
                ->label('Please specify')
                ->maxLength(255)
                ->visible(fn (Get $get) => in_array($get('license_name'), ['RA1080', "Other's"], true))
                ->required(fn (Get $get) => in_array($get('license_name'), ['RA1080', "Other's"], true)),

            Forms\Components\TextInput::make('rating')
                ->label('Rating')
                ->maxLength(100),

            Forms\Components\DatePicker::make('date_issued')
                ->label('Date Issued')
                ->nullable(),

            Forms\Components\DatePicker::make('valid_until')
                ->label('Valid Until')
                ->nullable()
                ->visible(fn (Get $get) => ! $get('never_expires'))
                ->dehydrated(fn (Get $get) => ! $get('never_expires')),

            Forms\Components\Toggle::make('never_expires')
                ->label('Never Expires')
                ->live(),
        ])->columns(2);
    }
}