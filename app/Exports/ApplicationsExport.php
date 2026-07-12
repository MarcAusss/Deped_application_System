<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ApplicationsExport implements FromCollection, WithHeadings
{
    protected Builder $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function collection()
    {
        return $this->query
            ->with([
                'profile',
                'jobPosition',
                'controlNumber',
                'evaluation',
            ])
            ->get()
            ->map(function ($application) {
                return [
                    $application->controlNumber?->control_number,
                    $application->profile?->full_name,
                    $application->profile?->email,
                    $application->jobPosition?->title,
                    ucfirst($application->status),
                    $application->evaluation?->recommended ? 'Yes' : 'No',
                    $application->evaluation?->evaluated_at,
                    $application->created_at,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Control Number',
            'Applicant Name',
            'Email',
            'Position',
            'Status',
            'Recommended',
            'Evaluated At',
            'Applied At',
        ];
    }
}
