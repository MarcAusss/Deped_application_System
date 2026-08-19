<?php

namespace App\Filament\Resources\Archive\Pages;

use App\Filament\Resources\Archive\ArchiveResource;
use Filament\Resources\Pages\ListRecords;

class ListArchive extends ListRecords
{
    protected static string $resource = ArchiveResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
