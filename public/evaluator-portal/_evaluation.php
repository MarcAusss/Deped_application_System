<?php

if (!defined('PORTAL_BOOTED')) {
    http_response_code(404);
    exit;
}

/**
 * Pure-PHP port of app/Support/EvaluationChecklist.php — kept in exact sync
 * with that file by convention (there is no code-sharing mechanism between
 * this PDO-based portal and the Eloquent-based Laravel app). If the source
 * file changes, this file must be updated to match.
 */

const EVAL_RESULT_PENDING = 'pending_document_review';
const EVAL_RESULT_QUALIFIED = 'qualified';
const EVAL_RESULT_NOT_QUALIFIED = 'not_qualified';
const EVAL_RESULT_EXCLUDED = 'excluded';

function eval_mandatory_requirements(): array
{
    return [
        'letter_of_intent' => '[Required] Letter of Intent addressed to the Head of Office or Highest Human Resource Officer',
        'eligibility_certificate' => '[Required] Photocopy of Certificate of Eligibility/Report of Rating, if applicable',
        'academic_records' => '[Required] Photocopy of Scholastic/Academic Records such as Transcript of Records (TOR) and Diploma, including graduate and post-graduate units/degrees, if available',
        'pds_work_experience' => '[Required] Duly accomplished Personal Data Sheet (PDS, CS Form No. 212, Revised 2017) and Work Experience Sheet, if applicable',
        'checklist_omnibus_sworn' => '[Required] Checklist of Requirements and Omnibus Sworn Statement on the CAV of documents submitted and Data Privacy Consent Form',
    ];
}

function eval_other_requirements(): array
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

function eval_mandatory_count(): int
{
    return count(eval_mandatory_requirements());
}

function eval_other_count(): int
{
    return count(eval_other_requirements());
}

function eval_count_selected(array $requirements, mixed $selected): int
{
    $selected = is_array($selected) ? $selected : [];

    return count(array_intersect(array_keys($requirements), $selected));
}

function eval_is_documentary_complete(mixed $mandatorySelected): bool
{
    $mandatorySelected = is_array($mandatorySelected) ? $mandatorySelected : [];

    return count(array_intersect(array_keys(eval_mandatory_requirements()), $mandatorySelected)) === eval_mandatory_count();
}

function eval_is_qualified(mixed $education, mixed $experience, mixed $training, mixed $eligibility): bool
{
    return $education === true && $experience === true && $training === true && $eligibility === true;
}

function eval_has_any_disqualification(mixed $education, mixed $experience, mixed $training, mixed $eligibility): bool
{
    return $education === false || $experience === false || $training === false || $eligibility === false;
}

/** @return array<int, string> */
function eval_disqualified_categories(mixed $education, mixed $experience, mixed $training, mixed $eligibility): array
{
    $categories = [
        "Bachelor's Degree" => $education,
        'Years of Experience' => $experience,
        'Hours of Training' => $training,
        'Eligibility' => $eligibility,
    ];

    return array_keys(array_filter($categories, fn ($met) => $met === false));
}

function eval_compute_result(
    mixed $mandatorySelected,
    mixed $education,
    mixed $experience,
    mixed $training,
    mixed $eligibility,
    bool $currentlyExcluded = false
): string {
    if (!eval_is_documentary_complete($mandatorySelected)) {
        return EVAL_RESULT_PENDING;
    }

    if (eval_is_qualified($education, $experience, $training, $eligibility)) {
        return EVAL_RESULT_QUALIFIED;
    }

    if ($currentlyExcluded) {
        return EVAL_RESULT_EXCLUDED;
    }

    if (eval_has_any_disqualification($education, $experience, $training, $eligibility)) {
        return EVAL_RESULT_NOT_QUALIFIED;
    }

    return EVAL_RESULT_PENDING;
}

function eval_result_label(string $result): string
{
    return match ($result) {
        EVAL_RESULT_QUALIFIED => 'Qualified',
        EVAL_RESULT_NOT_QUALIFIED => 'Not Qualified',
        EVAL_RESULT_EXCLUDED => 'Excluded',
        default => 'Pending Document Review',
    };
}

function eval_result_color(string $result): string
{
    return match ($result) {
        EVAL_RESULT_QUALIFIED => 'success',
        EVAL_RESULT_NOT_QUALIFIED, EVAL_RESULT_EXCLUDED => 'danger',
        default => 'gray',
    };
}

function eval_result_description(string $result, array $disqualifiedCategories = []): string
{
    return match ($result) {
        EVAL_RESULT_QUALIFIED => 'Documents are complete and all qualification standards are marked Meet the QS.',
        EVAL_RESULT_NOT_QUALIFIED => $disqualifiedCategories === []
            ? 'At least one qualification standard is marked Did not Meet the QS.'
            : 'Did not Meet the QS: '.implode(', ', $disqualifiedCategories).'.',
        EVAL_RESULT_EXCLUDED => 'This applicant does Not Meet Qualification Standards.',
        default => 'The status defaults to Pending Document Review until mandatory requirements are complete. Applicants are marked Qualified when all qualification standards are marked Meet the QS. Applicants can be marked Excluded only when specifically using the Exclude button below.',
    };
}

/** Normalize a MySQL tinyint(1)/NULL value into a strict PHP bool|null. */
function eval_bool(mixed $v): ?bool
{
    if ($v === null) {
        return null;
    }

    return (bool) (int) $v;
}

/** @param array<int, object> $educations */
function eval_applicant_bachelors_degree(array $educations): string
{
    foreach ($educations as $education) {
        if ($education->level === "Bachelor's Degree") {
            return $education->degree ?: 'Not specified';
        }
    }

    return 'Not provided';
}

/** @param array<int, object> $experiences */
function eval_applicant_years_of_experience(array $experiences): string
{
    $totalDays = 0;

    foreach ($experiences as $experience) {
        $start = strtotime((string) $experience->first_day);
        $end = $experience->last_day ? strtotime((string) $experience->last_day) : time();

        if ($start === false || $end === false) {
            continue;
        }
        if ($end < $start) {
            continue;
        }

        $totalDays += ($end - $start) / 86400;
    }

    $years = round($totalDays / 365, 2);

    return $years.' year(s)';
}

/** @param array<int, object> $trainings */
function eval_applicant_hours_of_training(array $trainings): string
{
    $total = 0;
    foreach ($trainings as $training) {
        $total += (int) ($training->hours ?? 0);
    }

    return $total.' hour(s)';
}

/** @param array<int, object> $eligibilities */
function eval_applicant_eligibility(array $eligibilities): string
{
    $names = [];
    foreach ($eligibilities as $eligibility) {
        if (filled_str($eligibility->license_name ?? null) && !in_array($eligibility->license_name, $names, true)) {
            $names[] = $eligibility->license_name;
        }
    }

    return $names === [] ? 'Not provided' : implode(' / ', $names);
}

function filled_str(?string $v): bool
{
    return $v !== null && $v !== '';
}

/**
 * Pure-PHP port of app/Support/IerApplicationFormatter.php. Each argument is
 * a plain object/array fetched via PDO (not an Eloquent model).
 *
 * @param object $application applications row (must include profile fields via join, or pass null)
 * @param object|null $profile applicant_profiles row
 * @param array<int, object> $educations
 * @param array<int, object> $trainings
 * @param array<int, object> $experiences
 * @param array<int, object> $eligibilities
 * @param string|null $controlNumber
 * @param object|null $evaluation application_evaluations row
 */
function eval_ier_row(
    int $number,
    ?object $profile,
    array $educations,
    array $trainings,
    array $experiences,
    array $eligibilities,
    ?string $controlNumber,
    ?object $evaluation,
    string $applicationStatus
): array {
    $birthDate = $profile->birth_date ?? null;

    return [
        'number' => $number,
        'application_code' => $controlNumber ?: '—',
        'name' => ($profile->full_name ?? null) ?: '—',
        'address' => ($profile->address ?? null) ?: '—',
        'age' => $birthDate ? eval_age_from_date($birthDate) : null,
        'sex' => ($profile->sex ?? null) ?: '—',
        'civil_status' => ($profile->civil_status ?? null) ?: '—',
        'religion' => ($profile->religion ?? null) ?: '—',
        'disability' => ($profile->disability ?? null) ?: 'None',
        'ethnic_group' => ($profile->ethnic_group ?? null) ?: '—',
        'email' => ($profile->email ?? null) ?: '—',
        'contact_number' => ($profile->phone ?? null) ?: '—',
        'education' => eval_ier_education($educations),
        'training_title' => eval_ier_training_titles($trainings),
        'training_hours' => eval_ier_training_hours($trainings),
        'experience_details' => eval_ier_experience_details($experiences),
        'experience_years' => eval_ier_experience_years($experiences),
        'eligibility' => eval_ier_eligibilities($eligibilities),
        'remarks' => eval_ier_remarks($evaluation, $applicationStatus),
    ];
}

function eval_age_from_date(string $date): ?int
{
    $ts = strtotime($date);
    if ($ts === false) {
        return null;
    }
    $birth = new DateTime(date('Y-m-d', $ts));
    $now = new DateTime('today');

    return $birth->diff($now)->y;
}

function eval_ier_position_summary(?object $position): array
{
    $salaryParts = [];

    if ($position && filled_str((string) ($position->salary_grade ?? ''))) {
        $salaryParts[] = 'SG '.$position->salary_grade;
    }
    if ($position && $position->monthly_salary !== null && $position->monthly_salary !== '') {
        $salaryParts[] = '₱'.number_format((float) $position->monthly_salary, 2);
    }

    return [
        'position' => $position->title ?? '',
        'salary' => implode(' / ', $salaryParts),
        'education_requirement' => $position->education_requirement ?? '',
        'training_requirement' => $position->training_requirement ?? '',
        'experience_requirement' => $position->experience_requirement ?? '',
        'eligibility_requirement' => $position->eligibility_requirement ?? '',
    ];
}

function eval_ier_education(array $educations): string
{
    $entries = [];
    foreach ($educations as $education) {
        $level = $education->level;
        if ($level === "Other's" && filled_str($education->level_specify ?? null)) {
            $level .= ' - '.$education->level_specify;
        }
        $qualification = filled_str($education->degree ?? null) ? $education->degree : $level;

        $parts = array_filter([$qualification, $education->school ?? null], fn ($v) => filled_str($v));
        $text = implode(' — ', $parts);

        if (filled_str($education->year_graduated ?? null)) {
            $text .= ($text !== '' ? ' ' : '').'('.$education->year_graduated.')';
        }
        if ($text !== '') {
            $entries[] = $text;
        }
    }

    return $entries === [] ? '—' : implode("\n", $entries);
}

function eval_ier_training_titles(array $trainings): string
{
    $entries = [];
    foreach ($trainings as $training) {
        $title = $training->title ?: '';
        if (filled_str($training->training_date ?? null)) {
            $date = date('F Y', strtotime($training->training_date));
            if (filled_str($training->training_end_date ?? null)) {
                $date .= ' - '.date('F Y', strtotime($training->training_end_date));
            }
            $title .= ($title !== '' ? ' ' : '').'('.$date.')';
        }
        if ($title !== '') {
            $entries[] = $title;
        }
    }

    return $entries === [] ? '—' : implode("\n", $entries);
}

function eval_ier_training_hours(array $trainings): ?int
{
    $hours = [];
    foreach ($trainings as $training) {
        if ($training->hours !== null && $training->hours !== '') {
            $hours[] = (int) $training->hours;
        }
    }

    return $hours === [] ? null : array_sum($hours);
}

function eval_ier_experience_details(array $experiences): string
{
    $entries = [];
    foreach ($experiences as $experience) {
        $heading = implode(' — ', array_filter([$experience->title ?? null, $experience->company ?? null], fn ($v) => filled_str($v)));
        $text = implode(': ', array_filter([$heading !== '' ? $heading : null, $experience->details ?? null], fn ($v) => filled_str($v)));
        if ($text !== '') {
            $entries[] = $text;
        }
    }

    return $entries === [] ? '—' : implode("\n", $entries);
}

function eval_ier_experience_years(array $experiences): string
{
    $entries = [];
    foreach ($experiences as $experience) {
        if (filled_str($experience->years_months ?? null)) {
            $entries[] = $experience->years_months;
            continue;
        }
        $range = implode(' - ', array_filter([$experience->first_day ?? null, $experience->last_day ?? null], fn ($v) => filled_str($v)));
        if ($range !== '') {
            $entries[] = $range;
        }
    }

    return $entries === [] ? '—' : implode("\n", $entries);
}

function eval_ier_eligibilities(array $eligibilities): string
{
    $entries = [];
    foreach ($eligibilities as $eligibility) {
        $details = implode('; ', array_filter([
            filled_str($eligibility->rating ?? null) ? 'Rating: '.$eligibility->rating : null,
            filled_str($eligibility->date_issued ?? null) ? 'Date issued: '.date('M d, Y', strtotime($eligibility->date_issued)) : null,
            eval_bool($eligibility->never_expires ?? null)
                ? 'Valid until: Never Expires'
                : (filled_str($eligibility->valid_until ?? null) ? 'Valid until: '.date('M d, Y', strtotime($eligibility->valid_until)) : null),
        ], fn ($v) => $v !== null));

        $name = $eligibility->license_name ?: '';
        if (in_array($name, ['RA1080', "Other's"], true) && filled_str($eligibility->license_specify ?? null)) {
            $name .= ' - '.$eligibility->license_specify;
        }

        $row = trim($name.($details !== '' ? ' ('.$details.')' : ''));
        if ($row !== '') {
            $entries[] = $row;
        }
    }

    return $entries === [] ? '—' : implode("\n", $entries);
}

function eval_ier_remarks(?object $evaluation, string $applicationStatus): string
{
    if (!$evaluation) {
        return 'Status: '.ucfirst($applicationStatus);
    }

    $parts = array_filter([
        $evaluation->remarks ?? null,
        eval_bool($evaluation->recommended ?? null) ? 'Recommended' : 'Not Recommended',
    ], fn ($v) => filled_str($v));

    return implode("\n", $parts);
}
