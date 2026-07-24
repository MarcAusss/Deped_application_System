<?php

namespace App\Exports;

use App\Models\JobPosition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ApplicationsExport implements WithMultipleSheets
{
    public function __construct(
        protected Builder $query
    ) {
    }

    public function sheets(): array
    {
        $applications = (clone $this->query)
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

        if ($applications->isEmpty()) {
            return [
                new ApplicationsIerSheet(
                    applications: collect(),
                    position: null,
                    sheetTitle: 'IER'
                ),
            ];
        }

        $usedTitles = [];
        $sheets = [];

        $applications
            ->groupBy(fn ($application) => (string) ($application->job_position_id ?? 'unassigned'))
            ->each(function (Collection $positionApplications) use (&$sheets, &$usedTitles): void {
                /** @var JobPosition|null $position */
                $position = $positionApplications->first()?->jobPosition;
                $sheetTitle = $this->uniqueSheetTitle(
                    $position?->title ?: 'Unassigned Position',
                    $usedTitles
                );

                $sheets[] = new ApplicationsIerSheet(
                    applications: $positionApplications->values(),
                    position: $position,
                    sheetTitle: $sheetTitle
                );
            });

        return $sheets;
    }

    private function uniqueSheetTitle(string $title, array &$usedTitles): string
    {
        $cleanTitle = trim((string) preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', '', $title));
        $baseTitle = mb_substr($cleanTitle !== '' ? $cleanTitle : 'IER', 0, 31);
        $sheetTitle = $baseTitle;
        $counter = 2;

        while (in_array(mb_strtolower($sheetTitle), $usedTitles, true)) {
            $suffix = " ({$counter})";
            $sheetTitle = mb_substr($baseTitle, 0, 31 - mb_strlen($suffix)).$suffix;
            $counter++;
        }

        $usedTitles[] = mb_strtolower($sheetTitle);

        return $sheetTitle;
    }
}
