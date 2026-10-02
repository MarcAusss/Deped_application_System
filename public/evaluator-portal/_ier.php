<?php

if (!defined('PORTAL_BOOTED')) {
    http_response_code(404);
    exit;
}

/**
 * Resolve the set of application IDs an IER preview/export request targets —
 * either an explicit selection (source=selected, ids[]) or the current
 * Applications-list filters (source=filtered, status/job), matching the
 * header-level "export filtered list" vs. per-row "export selected" split
 * that only exists on the Applications page in the source system.
 *
 * @return array<int, int>
 */
function eval_ier_resolve_ids(): array
{
    $source = $_POST['source'] ?? 'selected';

    if ($source === 'filtered') {
        $status = $_POST['status'] ?? '';
        $job = $_POST['job'] ?? '';

        $where = ["a.status NOT IN ('qualified','disqualified')"];
        $params = [];
        if (in_array($status, ['pending', 'evaluated', 'excluded'], true)) {
            $where[] = 'a.status = :status';
            $params['status'] = $status;
        }
        if ($job !== '' && ctype_digit((string) $job)) {
            $where[] = 'a.job_position_id = :job';
            $params['job'] = $job;
        }

        $stmt = portal_pdo()->prepare('SELECT a.id FROM applications a WHERE '.implode(' AND ', $where));
        $stmt->execute($params);

        return array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id'));
    }

    $ids = $_POST['ids'] ?? [];

    return array_values(array_unique(array_map('intval', array_filter($ids, fn ($v) => ctype_digit((string) $v)))));
}

/**
 * Load full IER data for the given application IDs, grouped by
 * job_position_id (or "unassigned"), each group carrying its job_positions
 * row and an ordered list of applications (each with profile/repeatable
 * rows/control number/evaluation attached) — mirrors ApplicationsExport's
 * groupBy() + eager-loaded relations.
 *
 * @param array<int, int> $ids
 * @return array<int, array{position: object|null, applications: array<int, object>}>
 */
function eval_ier_load_groups(array $ids): array
{
    if ($ids === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = portal_pdo()->prepare("SELECT * FROM applications WHERE id IN ({$placeholders}) ORDER BY id");
    $stmt->execute($ids);
    $applications = $stmt->fetchAll();

    $jobIds = array_values(array_unique(array_filter(array_map(fn ($a) => $a->job_position_id, $applications))));
    $jobs = [];
    if ($jobIds !== []) {
        $jp = implode(',', array_fill(0, count($jobIds), '?'));
        $stmt = portal_pdo()->prepare("SELECT * FROM job_positions WHERE id IN ({$jp})");
        $stmt->execute($jobIds);
        foreach ($stmt->fetchAll() as $job) {
            $jobs[$job->id] = $job;
        }
    }

    $fetchIndexed = function (string $table) use ($ids, $placeholders): array {
        $stmt = portal_pdo()->prepare("SELECT * FROM {$table} WHERE application_id IN ({$placeholders}) ORDER BY id");
        $stmt->execute($ids);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[$row->application_id][] = $row;
        }

        return $out;
    };

    $profiles = [];
    $stmt = portal_pdo()->prepare("SELECT * FROM applicant_profiles WHERE application_id IN ({$placeholders})");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $row) {
        $profiles[$row->application_id] = $row;
    }

    $controlNumbers = [];
    $stmt = portal_pdo()->prepare("SELECT * FROM application_control_numbers WHERE application_id IN ({$placeholders})");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $row) {
        $controlNumbers[$row->application_id] = $row->control_number;
    }

    $evaluations = [];
    $stmt = portal_pdo()->prepare("SELECT * FROM application_evaluations WHERE application_id IN ({$placeholders})");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $row) {
        $evaluations[$row->application_id] = $row;
    }

    $educations = $fetchIndexed('applicant_educations');
    $experiences = $fetchIndexed('applicant_experiences');
    $trainings = $fetchIndexed('applicant_trainings');
    $eligibilities = $fetchIndexed('applicant_eligibilities');

    $groups = [];
    foreach ($applications as $app) {
        $key = $app->job_position_id ?: 'unassigned';
        if (!isset($groups[$key])) {
            $groups[$key] = [
                'position' => $app->job_position_id ? ($jobs[$app->job_position_id] ?? null) : null,
                'applications' => [],
            ];
        }

        $app->_profile = $profiles[$app->id] ?? null;
        $app->_control_number = $controlNumbers[$app->id] ?? null;
        $app->_evaluation = $evaluations[$app->id] ?? null;
        $app->_educations = $educations[$app->id] ?? [];
        $app->_experiences = $experiences[$app->id] ?? [];
        $app->_trainings = $trainings[$app->id] ?? [];
        $app->_eligibilities = $eligibilities[$app->id] ?? [];

        $groups[$key]['applications'][] = $app;
    }

    return array_values($groups);
}

function eval_ier_unique_sheet_title(string $title, array &$usedTitles): string
{
    $cleanTitle = trim((string) preg_replace('/[\\\\\/\?\*\[\]:]/', '', $title));
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
