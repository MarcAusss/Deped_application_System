<?php

namespace App\Filament\Evaluator\Widgets;

use App\Filament\Evaluator\Resources\Applications\ApplicationResource;
use App\Models\ApplicationEvaluation;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentEvaluations extends BaseWidget
{
    protected int|string|array $columnSpan = 2;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Your recent evaluations')
            ->description('Latest assessments completed using your evaluator account.')
            ->query(
                ApplicationEvaluation::query()
                    ->with([
                        'application.profile',
                        'application.jobPosition',
                    ])
                    ->where('evaluator_id', auth()->id())
                    ->latest('evaluated_at')
                    ->limit(6)
            )
            ->columns([
                Tables\Columns\TextColumn::make('application.profile.full_name')
                    ->label('Applicant')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('application.jobPosition.title')
                    ->label('Position'),

                Tables\Columns\IconColumn::make('recommended')
                    ->label('Recommended')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('evaluated_at')
                    ->label('Evaluated')
                    ->dateTime('M d, Y h:i A'),
            ])
            ->recordUrl(
                fn (ApplicationEvaluation $record): ?string => $record->application
                    ? ApplicationResource::getUrl('view', ['record' => $record->application])
                    : null
            )
            ->paginated(false)
            ->emptyStateHeading('No evaluations completed')
            ->emptyStateDescription('Your completed assessments will be listed here.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check');
    }
}
