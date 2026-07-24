<?php

namespace App\Filament\Evaluator\Widgets;

use App\Filament\Evaluator\Resources\Applications\ApplicationResource;
use App\Models\Application;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentApplications extends TableWidget
{
    protected int|string|array $columnSpan = 2;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Latest applications')
            ->description('New records that may require evaluator review.')
            ->query(
                Application::query()
                    ->with([
                        'profile',
                        'jobPosition',
                        'controlNumber',
                    ])
                    ->latest()
                    ->limit(6)
            )

            ->columns([
                Tables\Columns\TextColumn::make('controlNumber.control_number')
                    ->label('Control #')
                    ->placeholder('Not assigned')
                    ->searchable(),

                Tables\Columns\TextColumn::make('profile.full_name')
                    ->label('Applicant')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Application $record): ?string => $record->profile?->email),

                Tables\Columns\TextColumn::make('jobPosition.title')
                    ->label('Position'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'pending' => 'gray',
                        'evaluated' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Applied')
                    ->date('M d, Y'),
            ])

            ->recordUrl(fn (Application $record) => ApplicationResource::getUrl('view', [
                'record' => $record,
            ]))

            ->paginated(false)

            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No applications yet')
            ->emptyStateDescription('New applicant submissions will appear here.')
            ->emptyStateIcon('heroicon-o-document-text');
    }
}
