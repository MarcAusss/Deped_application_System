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
            $level = $education->level;

            if ($level === "Other's" && filled($education->level_specify)) {
                $level .= ' - '.$education->level_specify;
            }

            $qualification = $education->degree ?: $level;
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
            ->map(function ($training): string {
                $title = $training->title ?: '';

                if (filled($training->training_date)) {
                    $date = Carbon::parse($training->training_date)->format('F Y');

                    if (filled($training->training_end_date)) {
                        $date .= ' - '.Carbon::parse($training->training_end_date)->format('F Y');
                    }

                    $title .= ($title !== '' ? ' ' : '').'('.$date.')';
                }

                return $title;
            })
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
            ->map(function ($experience): ?string {
                if (filled($experience->years_months)) {
                    return $experience->years_months;
                }

                $range = collect([$experience->first_day, $experience->last_day])
                    ->filter(fn ($value) => filled($value))
                    ->implode(' - ');

                return filled($range) ? $range : null;
            })
            ->filter(fn ($value) => filled($value));

        return $entries->isNotEmpty() ? $entries->implode("\n") : '—';
    }

    private static function eligibilities(Application $application): string
    {
        $entries = $application->eligibilities->map(function ($eligibility): string {
            $details = collect([
                filled($eligibility->rating) ? 'Rating: '.$eligibility->rating : null,
                filled($eligibility->date_issued)
                    ? 'Date issued: '.Carbon::parse($eligibility->date_issued)->format('M d, Y')
                    : null,
                $eligibility->never_expires
                    ? 'Valid until: Never Expires'
                    : (filled($eligibility->valid_until)
                        ? 'Valid until: '.Carbon::parse($eligibility->valid_until)->format('M d, Y')
                        : null),
            ])->filter()->implode('; ');

            $name = $eligibility->license_name ?: '';

            if (in_array($name, ['RA1080', "Other's"], true) && filled($eligibility->license_specify)) {
                $name .= ' - '.$eligibility->license_specify;
            }

            return trim($name.($details !== '' ? ' ('.$details.')' : ''));
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
