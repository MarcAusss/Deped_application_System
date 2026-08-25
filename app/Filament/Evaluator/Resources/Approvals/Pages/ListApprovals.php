<?php

namespace App\Filament\Evaluator\Resources\Approvals\Pages;

use App\Filament\Evaluator\Resources\Approvals\ApprovalResource;
use Filament\Resources\Pages\ListRecords;

class ListApprovals extends ListRecords
{
    protected static string $resource = ApprovalResource::class;

    public function getPageClasses(): array
    {
        return [...parent::getPageClasses(), 'applications-list-page'];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
