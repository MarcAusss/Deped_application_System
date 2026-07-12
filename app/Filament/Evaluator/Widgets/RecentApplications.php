<?php

namespace App\Filament\Evaluator\Widgets;

use App\Filament\Evaluator\Resources\Applications\ApplicationResource;
use App\Models\Application;
use Filament\Actions\BulkActionGroup;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentApplications extends TableWidget
{
    protected static ?string $heading = 'Recent Applications';

    protected int|string|array $columnSpan = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Application::query()
                    ->with([
                        'profile',
                        'jobPosition',
                        'controlNumber',
                    ])
                    ->latest()
            )

            ->columns([
                Tables\Columns\TextColumn::make('controlNumber.control_number')
                    ->label('Control #')
                    ->placeholder('Not assigned')
                    ->searchable(),

                Tables\Columns\TextColumn::make('profile.full_name')
                    ->label('Applicant')
                    ->searchable(),

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

            ->paginated([5])

            ->defaultSort('created_at', 'desc')

            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }
}
