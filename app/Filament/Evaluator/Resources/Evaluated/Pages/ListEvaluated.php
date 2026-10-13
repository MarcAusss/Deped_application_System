<?php

namespace App\Filament\Evaluator\Resources\Evaluated\Pages;

use App\Filament\Evaluator\Resources\Evaluated\EvaluatedResource;
use Filament\Resources\Pages\ListRecords;

class ListEvaluated extends ListRecords
{
    protected static string $resource = EvaluatedResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
