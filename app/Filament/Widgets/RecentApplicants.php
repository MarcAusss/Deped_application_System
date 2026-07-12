<?php

namespace App\Filament\Widgets;

use App\Models\Application;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentApplicants extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Recent Applicants';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Application::query()
                    ->with('jobPosition')
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('full_name')
                    ->label('Applicant')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('jobPosition.title')
                    ->label('Job Position')
                    ->badge()
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
            ->paginated(false)
            ->emptyStateHeading('No applications yet')
            ->emptyStateDescription(
                'Newly submitted applications will appear here.'
            )
            ->emptyStateIcon('heroicon-o-document-text');
    }
}