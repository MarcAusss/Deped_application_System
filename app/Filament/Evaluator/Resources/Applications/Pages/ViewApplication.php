<?php

namespace App\Filament\Evaluator\Resources\Applications\Pages;

use App\Filament\Evaluator\Resources\Applications\ApplicationResource;
use App\Filament\Resources\Applications\ApplicationResource as ApplicationDetailsResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewApplication extends ViewRecord
{
    protected static string $resource = ApplicationResource::class;

    public function form(Schema $schema): Schema
    {
        return ApplicationDetailsResource::form($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->outlined()
                ->extraAttributes(['style' => 'box-shadow:inset 0 0 0 1px #1e3a8a;color:#1e3a8a;background-color:transparent;']),
        ];
    }
}