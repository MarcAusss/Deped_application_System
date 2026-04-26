<?php

namespace App\Filament\Resources\Applications;

use App\Models\Application;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Schemas\Schema;

use App\Filament\Resources\Applications\Pages;

class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    // ✅ FILAMENT v5 uses Schema (NOT Form)
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('full_name'),
                Forms\Components\TextInput::make('email'),

                Forms\Components\Checkbox::make('resume_checked'),
                Forms\Components\Checkbox::make('credentials_valid'),
                Forms\Components\Checkbox::make('recommended'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'pending' => 'gray',
                        'evaluated' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    }),

                Tables\Columns\IconColumn::make('evaluated_by_evaluator')
                    ->boolean()
                    ->label('Evaluated'),

                Tables\Columns\IconColumn::make('resume_checked')
                    ->boolean(),

                Tables\Columns\IconColumn::make('credentials_valid')
                    ->boolean(),

                Tables\Columns\IconColumn::make('recommended')
                    ->boolean(),
            ])

            // 🔍 FILTERS (VERY IMPORTANT FOR ADMIN)
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'evaluated' => 'Evaluated',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
            ])

            // ⚡ ADMIN ACTIONS
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->action(fn($record) => $record->update([
                        'status' => 'approved',
                        'final_reviewed_by_admin' => true,
                    ]))
                    ->visible(fn($record) => $record->status === 'evaluated'),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->action(fn($record) => $record->update([
                        'status' => 'rejected',
                        'final_reviewed_by_admin' => true,
                    ]))
                    ->visible(fn($record) => $record->status !== 'approved'),

                Tables\Actions\EditAction::make(),
            ])

            // 🧺 BULK ACTIONS (optional but useful)
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplications::route('/'),
            'create' => Pages\CreateApplication::route('/create'),
            'edit' => Pages\EditApplication::route('/{record}/edit'),
        ];
    }
}