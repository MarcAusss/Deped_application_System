<?php

namespace App\Filament\Resources\Archive;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\Archive\Pages;
use App\Models\Application;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ArchiveResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Archive';

    protected static ?string $modelLabel = 'Archived Application';

    protected static ?string $pluralModelLabel = 'Archive';

    protected static ?string $slug = 'archive';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'Applications';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('status', 'rejected');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('controlNumber.control_number')
                    ->label('Control #')
                    ->placeholder('Not assigned')
                    ->sortable()
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('profile.full_name')
                    ->label('Applicant Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('profile.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('jobPosition.title')
                    ->label('Position Applied')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Rejected On')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])

            ->defaultSort('updated_at', 'desc')

            ->recordActionsColumnLabel('Actions')
            ->recordActionsAlignment(\Filament\Support\Enums\Alignment::Center->value)

            ->filters([
                Tables\Filters\SelectFilter::make('job_position_id')
                    ->label('Filter by Position')
                    ->relationship('jobPosition', 'title'),
            ])

            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => ApplicationResource::getUrl('view', ['record' => $record])),

                Action::make('restore')
                    ->label('Restore')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Restore Application')
                    ->modalDescription('This moves the application out of the archive and back to evaluated status for reconsideration.')
                    ->modalSubmitActionLabel('Yes, restore')
                    ->action(function ($record) {
                        $record->update(['status' => 'evaluated']);

                        $record->logs()->create([
                            'status' => 'evaluated',
                            'changed_by' => Auth::id(),
                        ]);

                        Notification::make()
                            ->title('Application restored from archive')
                            ->success()
                            ->send();
                    }),
            ])

            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArchive::route('/'),
        ];
    }
}
