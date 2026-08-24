<?php

namespace App\Filament\Resources\PublicationOfVacancy\Pages;

use App\Filament\Resources\PublicationOfVacancy\PublicationOfVacancyResource;
use Filament\Resources\Pages\ListRecords;

class ListPublicationOfVacancy extends ListRecords
{
    protected static string $resource = PublicationOfVacancyResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
