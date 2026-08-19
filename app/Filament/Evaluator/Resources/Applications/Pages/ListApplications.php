<?php

namespace App\Filament\Evaluator\Resources\Applications\Pages;

use App\Exports\ApplicationsExport;
use App\Filament\Evaluator\Resources\Applications\ApplicationResource;
use App\Support\IerApplicationFormatter;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Maatwebsite\Excel\Facades\Excel;

class ListApplications extends ListRecords
{
    protected static string $resource = ApplicationResource::class;

    public function getPageClasses(): array
    {
        return [...parent::getPageClasses(), 'applications-list-page'];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Preview & Export IER')
                ->icon('heroicon-o-document-magnifying-glass')
                ->color('success')
                ->modalHeading('Initial Evaluation Result Preview')
                ->modalDescription('Review the filtered records below before creating the Excel workbook.')
                ->modalWidth(Width::Full)
                ->stickyModalHeader()
                ->stickyModalFooter()
                ->modalSubmitActionLabel('Export Excel')
                ->modalCancelActionLabel('Cancel')
                ->modalContent(function () {
                    $applications = (clone $this->getFilteredTableQuery())
                        ->with([
                            'profile',
                            'jobPosition',
                            'controlNumber',
                            'educations',
                            'trainings',
                            'experiences',
                            'eligibilities',
                            'evaluation',
                        ])
                        ->get();

                    $groups = $applications
                        ->groupBy(fn ($application) => (string) ($application->job_position_id ?? 'unassigned'))
                        ->map(function ($positionApplications): array {
                            $position = $positionApplications->first()?->jobPosition;

                            return [
                                'position' => IerApplicationFormatter::positionSummary($position),
                                'rows' => $positionApplications
                                    ->take(10)
                                    ->values()
                                    ->map(fn ($application, int $index) => IerApplicationFormatter::row(
                                        $application,
                                        $index + 1
                                    ))
                                    ->all(),
                                'total' => $positionApplications->count(),
                            ];
                        })
                        ->values();

                    return view('filament.evaluator.actions.ier-export-preview', [
                        'groups' => $groups,
                        'totalApplications' => $applications->count(),
                    ]);
                })
                ->action(function () {
                    $query = clone $this->getFilteredTableQuery();

                    return Excel::download(
                        new ApplicationsExport($query),
                        'initial-evaluation-result-'.now()->format('Y-m-d-His').'.xlsx'
                    );
                }),
        ];
    }
}
