<?php

namespace App\Filament\Resources\Approvals;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\Approvals\Pages;
use App\Models\Application;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ApprovalResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationLabel = 'Approvals';

    protected static ?string $modelLabel = 'Approved Application';

    protected static ?string $pluralModelLabel = 'Approvals';

    protected static ?string $slug = 'approvals';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'Applications';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('status', 'approved');
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
                    ->label('Approved On')
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
                    ->modalDescription('This undoes the approval and moves the application back to the main Applications list for reconsideration.')
                    ->modalSubmitActionLabel('Yes, restore')
                    ->action(function ($record) {
                        $record->update(['status' => 'evaluated']);

                        $record->logs()->create([
                            'status' => 'evaluated',
                            'changed_by' => Auth::id(),
                        ]);

                        Notification::make()
                            ->title('Application restored to Applications')
                            ->success()
                            ->send();
                    }),
            ])

            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApprovals::route('/'),
        ];
    }
}
