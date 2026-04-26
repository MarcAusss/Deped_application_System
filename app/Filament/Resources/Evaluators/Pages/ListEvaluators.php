<?php

namespace App\Filament\Resources\Evaluators\Pages;

use App\Filament\Resources\Evaluators\EvaluatorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEvaluators extends ListRecords
{
    protected static string $resource = EvaluatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
