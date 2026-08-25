<?php

namespace App\Support;

use App\Models\Application;
use App\Models\ApplicationEvaluation;

class EvaluationChecklist
{
    /**
     * @return array<string, string>
     */
    public static function mandatoryRequirements(): array
    {
        return [
            'letter_of_intent' => '[Required] Letter of Intent addressed to the Head of Office or Highest Human Resource Officer',
            'eligibility_certificate' => '[Required] Photocopy of Certificate of Eligibility/Report of Rating, if applicable',
            'academic_records' => '[Required] Photocopy of Scholastic/Academic Records such as Transcript of Records (TOR) and Diploma, including graduate and post-graduate units/degrees, if available',
            'pds_work_experience' => '[Required] Duly accomplished Personal Data Sheet (PDS, CS Form No. 212, Revised 2017) and Work Experience Sheet, if applicable',
            'checklist_omnibus_sworn' => '[Required] Checklist of Requirements and Omnibus Sworn Statement on the CAV of documents submitted and Data Privacy Consent Form',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function otherRequirements(): array
    {
        return [
            'prc_license' => 'Photocopy of valid and updated PRC License/ID, if applicable',
            'employment_certificate' => 'Photocopy of Certificate of Employment, Contract of Service or duly signed Service Record, whichever is/are applicable',
            'latest_appointment' => 'Photocopy of Certificate of Latest Appointment, if applicable',
            'training_certificates' => 'Photocopy of Certificate/s of Training, if applicable',
            'performance_rating' => 'Photocopy of the Performance Rating in the last rating period(s) covering one (1) year performance prior to the deadline of submission, if applicable',
            'other_movs' => 'Other documents as may be required for comparative assessment (e.g. MOVs, or Performance Rating from relevant work experience)',
        ];
    }

    public static function mandatoryCount(): int
    {
        return count(self::mandatoryRequirements());
    }

    public static function otherCount(): int
    {
        return count(self::otherRequirements());
    }

    /**
     * How many of the given requirement keys are currently selected.
     *
     * Accepts mixed because this is fed by live form state (Livewire/Alpine), which can
     * transiently hand back a non-array value (e.g. during a DOM morph) rather than the
     * expected array of selected keys.
     *
     * @param  array<string, string>  $requirements
     */
    public static function countSelected(array $requirements, mixed $selected): int
    {
        $selected = is_array($selected) ? $selected : [];

        return count(array_intersect(array_keys($requirements), $selected));
    }

    /**
     * Whether all mandatory documentary requirements are ticked, unlocking the QS Evaluation.
     *
     * Accepts mixed because this is fed by live form state (Livewire/Alpine), which can
     * transiently hand back a non-array value (e.g. during a DOM morph) rather than the
     * expected array of selected keys.
     */
    public static function isDocumentaryComplete(mixed $mandatorySelected): bool
    {
        $mandatorySelected = is_array($mandatorySelected) ? $mandatorySelected : [];

        return count(array_intersect(
            array_keys(self::mandatoryRequirements()),
            $mandatorySelected
        )) === self::mandatoryCount();
    }

    /**
     * Whether all 4 qualification standard categories were marked "Meet the QS".
     */
    public static function isQualified(mixed $education, mixed $experience, mixed $training, mixed $eligibility): bool
    {
        return $education === true && $experience === true && $training === true && $eligibility === true;
    }

    public static function computeResult(
        mixed $mandatorySelected,
        mixed $education,
        mixed $experience,
        mixed $training,
        mixed $eligibility,
        bool $currentlyExcluded = false,
    ): string {
        if (! self::isDocumentaryComplete($mandatorySelected)) {
            return ApplicationEvaluation::RESULT_PENDING_DOCUMENT_REVIEW;
        }

        // Marking every QS category "Meet the QS" always wins, even over a
        // prior manual exclusion — that's how an evaluator reverses one.
        if (self::isQualified($education, $experience, $training, $eligibility)) {
            return ApplicationEvaluation::RESULT_QUALIFIED;
        }

        if ($currentlyExcluded) {
            return ApplicationEvaluation::RESULT_EXCLUDED;
        }

        return ApplicationEvaluation::RESULT_PENDING_DOCUMENT_REVIEW;
    }

    public static function resultLabel(string $result): string
    {
        return match ($result) {
            ApplicationEvaluation::RESULT_QUALIFIED => 'Qualified',
            ApplicationEvaluation::RESULT_EXCLUDED => 'Excluded',
            default => 'Pending Document Review',
        };
    }

    public static function resultColor(string $result): string
    {
        return match ($result) {
            ApplicationEvaluation::RESULT_QUALIFIED => 'success',
            ApplicationEvaluation::RESULT_EXCLUDED => 'danger',
            default => 'gray',
        };
    }

    public static function resultDescription(string $result): string
    {
        return match ($result) {
            ApplicationEvaluation::RESULT_QUALIFIED => 'Documents are complete and all qualification standards are marked Meet the QS.',
            ApplicationEvaluation::RESULT_EXCLUDED => 'Excluded: This applicant was manually excluded from further consideration.',
            default => 'The status defaults to Pending Document Review until mandatory requirements are complete. Applicants are marked Qualified when all qualification standards are marked Meet the QS. Applicants can be marked Excluded only when specifically using the Exclude button below.',
        };
    }

    public static function applicantBachelorsDegree(Application $application): string
    {
        $education = $application->educations
            ->firstWhere('level', "Bachelor's Degree");

        if (! $education) {
            return 'Not provided';
        }

        return $education->degree ?: 'Not specified';
    }

    public static function applicantYearsOfExperience(Application $application): string
    {
        $totalDays = 0;

        foreach ($application->experiences as $experience) {
            try {
                $start = \Carbon\Carbon::parse($experience->first_day);
                $end = $experience->last_day ? \Carbon\Carbon::parse($experience->last_day) : now();
            } catch (\Throwable $e) {
                continue;
            }

            if ($end->lessThan($start)) {
                continue;
            }

            $totalDays += $start->diffInDays($end);
        }

        $years = round($totalDays / 365, 2);

        return $years . ' year(s)';
    }

    public static function applicantHoursOfTraining(Application $application): string
    {
        $totalHours = $application->trainings->sum('hours');

        return $totalHours . ' hour(s)';
    }

    public static function applicantEligibility(Application $application): string
    {
        $names = $application->eligibilities
            ->map(fn ($eligibility) => $eligibility->license_name)
            ->filter()
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            return 'Not provided';
        }

        return $names->implode(' / ');
    }
}
