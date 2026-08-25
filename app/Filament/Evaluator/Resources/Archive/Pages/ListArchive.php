<?php

namespace App\Filament\Evaluator\Resources\Archive\Pages;

use App\Filament\Evaluator\Resources\Archive\ArchiveResource;
use Filament\Resources\Pages\ListRecords;

class ListArchive extends ListRecords
{
    protected static string $resource = ArchiveResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
