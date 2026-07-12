<?php

namespace App\Filament\Evaluator\Widgets;

use App\Models\ApplicationEvaluation;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentEvaluations extends BaseWidget
{
    protected static ?string $heading = 'Recent Evaluations';
protected int|string|array $columnSpan = 2;
    protected function getTableQuery(): Builder
    {
        return ApplicationEvaluation::query()
            ->with([
                'application.profile',
                'application.jobPosition',
            ])
            ->latest('evaluated_at');
    }

    protected function getTableColumns(): array
    {
        return [

            Tables\Columns\TextColumn::make('application.profile.full_name')
                ->label('Applicant'),

            Tables\Columns\TextColumn::make('application.jobPosition.title')
                ->label('Position'),

            Tables\Columns\IconColumn::make('recommended')
                ->boolean(),

            Tables\Columns\TextColumn::make('evaluated_at')
                ->dateTime('M d, Y'),
        ];
    }

}
