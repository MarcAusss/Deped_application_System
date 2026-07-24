<?php

namespace App\Support;

use App\Models\Application;
use App\Models\JobPosition;
use Carbon\Carbon;

final class IerApplicationFormatter
{
    public static function row(Application $application, int $number): array
    {
        $profile = $application->profile;
        $birthDate = $profile?->birth_date;

        return [
            'number' => $number,
            'application_code' => $application->controlNumber?->control_number ?: '—',
            'name' => $profile?->full_name ?: '—',
            'address' => $profile?->address ?: '—',
            'age' => $birthDate ? Carbon::parse($birthDate)->age : null,
            'sex' => $profile?->sex ?: '—',
            'civil_status' => $profile?->civil_status ?: '—',
            'religion' => $profile?->religion ?: '—',
            'disability' => $profile?->disability ?: 'None',
            'ethnic_group' => $profile?->ethnic_group ?: '—',
            'email' => $profile?->email ?: '—',
            'contact_number' => $profile?->phone ?: '—',
            'education' => self::education($application),
            'training_title' => self::trainingTitles($application),
            'training_hours' => self::trainingHours($application),
            'experience_details' => self::experienceDetails($application),
            'experience_years' => self::experienceYears($application),
            'eligibility' => self::eligibilities($application),
            'remarks' => self::remarks($application),
        ];
    }

    public static function positionSummary(?JobPosition $position): array
    {
        $salaryParts = [];

        if (filled($position?->salary_grade)) {
            $salaryParts[] = 'SG '.$position->salary_grade;
        }

        if (filled($position?->monthly_salary)) {
            $salaryParts[] = '₱'.number_format((float) $position->monthly_salary, 2);
        }

        return [
            'position' => $position?->title ?: '',
            'salary' => implode(' / ', $salaryParts),
            'education_requirement' => $position?->education_requirement ?: '',
            'training_requirement' => $position?->training_requirement ?: '',
            'experience_requirement' => $position?->experience_requirement ?: '',
            'eligibility_requirement' => $position?->eligibility_requirement ?: '',
        ];
    }

    private static function education(Application $application): string
    {
        $entries = $application->educations->map(function ($education): string {
            $qualification = $education->degree ?: $education->level;
            $text = collect([$qualification, $education->school])
                ->filter(fn ($value) => filled($value))
                ->implode(' — ');

            if (filled($education->year_graduated)) {
                $text .= ($text !== '' ? ' ' : '').'('.$education->year_graduated.')';
            }

            return $text;
        })->filter();

        return $entries->isNotEmpty() ? $entries->implode("\n") : '—';
    }

    private static function trainingTitles(Application $application): string
    {
        $entries = $application->trainings
            ->pluck('title')
            ->filter(fn ($value) => filled($value));

        return $entries->isNotEmpty() ? $entries->implode("\n") : '—';
    }

    private static function trainingHours(Application $application): ?int
    {
        $hours = $application->trainings
            ->pluck('hours')
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value);

        return $hours->isNotEmpty() ? $hours->sum() : null;
    }

    private static function experienceDetails(Application $application): string
    {
        $entries = $application->experiences->map(function ($experience): string {
            $heading = collect([$experience->title, $experience->company])
                ->filter(fn ($value) => filled($value))
                ->implode(' — ');

            return collect([$heading, $experience->details])
                ->filter(fn ($value) => filled($value))
                ->implode(': ');
        })->filter();

        return $entries->isNotEmpty() ? $entries->implode("\n") : '—';
    }

    private static function experienceYears(Application $application): string
    {
        $entries = $application->experiences
            ->pluck('years_months')
            ->filter(fn ($value) => filled($value));

        return $entries->isNotEmpty() ? $entries->implode("\n") : '—';
    }

    private static function eligibilities(Application $application): string
    {
        $entries = $application->eligibilities->map(function ($eligibility): string {
            $details = collect([
                filled($eligibility->rating) ? 'Rating: '.$eligibility->rating : null,
                filled($eligibility->valid_until)
                    ? 'Valid until: '.Carbon::parse($eligibility->valid_until)->format('M d, Y')
                    : null,
            ])->filter()->implode('; ');

            return trim(($eligibility->license_name ?: '').($details !== '' ? ' ('.$details.')' : ''));
        })->filter();

        return $entries->isNotEmpty() ? $entries->implode("\n") : '—';
    }

    private static function remarks(Application $application): string
    {
        $evaluation = $application->evaluation;

        return collect([
            $evaluation?->remarks,
            $evaluation
                ? ($evaluation->recommended ? 'Recommended' : 'Not Recommended')
                : 'Status: '.ucfirst($application->status),
        ])->filter(fn ($value) => filled($value))->implode("\n");
    }
}
