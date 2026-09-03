<?php

namespace App\Filament\Resources\ClosedJobPostings\Pages;

use App\Filament\Resources\ClosedJobPostings\ClosedJobPostingResource;
use Filament\Resources\Pages\ListRecords;

class ListClosedJobPostings extends ListRecords
{
    protected static string $resource = ClosedJobPostingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
