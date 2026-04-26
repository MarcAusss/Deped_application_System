<?php

namespace App\Filament\Resources\Evaluators\Pages;

use App\Filament\Resources\Evaluators\EvaluatorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEvaluator extends EditRecord
{
    protected static string $resource = EvaluatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
