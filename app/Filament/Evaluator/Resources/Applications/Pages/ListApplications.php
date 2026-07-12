<?php

namespace App\Filament\Evaluator\Resources\Applications\Pages;

use App\Exports\ApplicationsExport;
use App\Filament\Evaluator\Resources\Applications\ApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListApplications extends ListRecords
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Export to Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {

                    $query = $this->getFilteredTableQuery();

                    return Excel::download(
                        new ApplicationsExport($query),
                        'applications.xlsx'
                    );
                }),
        ];
    }
}
