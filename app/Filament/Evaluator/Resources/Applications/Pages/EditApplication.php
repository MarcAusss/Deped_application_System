<?php

namespace App\Filament\Evaluator\Resources\Applications\Pages;

use App\Filament\Evaluator\Resources\Applications\ApplicationResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditApplication extends EditRecord
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}