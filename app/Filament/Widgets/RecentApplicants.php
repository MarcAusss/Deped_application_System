<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Models\Application;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentApplicants extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent applicant activity')
            ->description('Latest submissions across all open and closed positions.')
            ->query(
                Application::query()
                    ->with([
                        'controlNumber',
                        'jobPosition',
                        'profile',
                    ])
                    ->latest()
                    ->limit(6)
            )
            ->columns([
                TextColumn::make('controlNumber.control_number')
                    ->label('Control No.')
                    ->placeholder('Not assigned')
                    ->copyable(),

                TextColumn::make('profile.full_name')
                    ->label('Applicant')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Application $record): ?string => $record->profile?->email),

                TextColumn::make('jobPosition.title')
                    ->label('Position')
                    ->placeholder('No position'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'pending' => 'gray',
                        'evaluated' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'evaluated' => 'For Approval',
                        null => 'Unknown',
                        default => str($state)
                            ->replace('_', ' ')
                            ->title()
                            ->toString(),
                    }),

                TextColumn::make('created_at')
                    ->label('Date Applied')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),
            ])
            ->recordUrl(fn (Application $record): string => ApplicationResource::getUrl('view', [
                'record' => $record,
            ]))
            ->paginated(false)
            ->emptyStateHeading('No applications yet')
            ->emptyStateDescription(
                'Newly submitted applications will appear here.'
            )
            ->emptyStateIcon('heroicon-o-document-text');
    }
}
