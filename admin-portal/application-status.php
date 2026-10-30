<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

admin_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
csrf_verify();

$admin = admin_user();
$id = (int) ($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

$referer = $_SERVER['HTTP_REFERER'] ?? admin_url('applications.php');

if (!$id || !in_array($action, ['qualify', 'disqualify'], true)) {
    http_response_code(400);
    exit;
}

$pdo = portal_pdo();

$stmt = $pdo->prepare('SELECT id, status, job_position_id FROM applications WHERE id = ?');
$stmt->execute([$id]);
$application = $stmt->fetch();

if (!$application) {
    http_response_code(404);
    exit;
}

// Qualify/Disqualify are only ever enabled from evaluated/excluded — mirrors
// ApplicationResource::table()'s row-action ->disabled() rule exactly.
if (!in_array($application->status, ['evaluated', 'excluded'], true)) {
    flash_set('error', 'Only evaluated or excluded applications can be marked Qualified/Disqualified.');
    header('Location: '.$referer);
    exit;
}

$newStatus = $action === 'qualify' ? 'qualified' : 'disqualified';

$pdo->prepare('UPDATE applications SET status = ?, updated_at = NOW() WHERE id = ?')->execute([$newStatus, $id]);
$pdo->prepare('INSERT INTO application_status_logs (application_id, status, changed_by, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())')
    ->execute([$id, $newStatus, $admin->id]);

// Mirrors ApplicationResource's qualify/disqualify actions: email failures
// are swallowed (just logged) so a broken mail server never blocks the
// finalized hiring decision that was already saved above.
$emailSent = null; // null = no email to send (no profile/address)
try {
    $stmt = $pdo->prepare('SELECT * FROM job_positions WHERE id = ?');
    $stmt->execute([$application->job_position_id]);
    $job = $stmt->fetch();

    $stmt = $pdo->prepare('SELECT full_name, email FROM applicant_profiles WHERE application_id = ?');
    $stmt->execute([$id]);
    $profile = $stmt->fetch();

    $stmt = $pdo->prepare('SELECT * FROM application_evaluations WHERE application_id = ?');
    $stmt->execute([$id]);
    $evaluation = $stmt->fetch();

    $stmt = $pdo->prepare('SELECT control_number FROM application_control_numbers WHERE application_id = ?');
    $stmt->execute([$id]);
    $controlNumber = $stmt->fetchColumn() ?: null;

    $fetchAll = function (string $table) use ($pdo, $id) {
        $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE application_id = ? ORDER BY id");
        $stmt->execute([$id]);

        return $stmt->fetchAll();
    };

    if ($job && $profile && $profile->email) {
        $qsRows = admin_build_qs_rows(
            $job,
            $evaluation,
            $fetchAll('applicant_educations'),
            $fetchAll('applicant_experiences'),
            $fetchAll('applicant_trainings'),
            $fetchAll('applicant_eligibilities')
        );
        $remarks = $evaluation->remarks ?? null;
        $isExcluded = $evaluation && $evaluation->result === EVAL_RESULT_EXCLUDED;

        if ($action === 'qualify') {
            $emailSent = send_qualified($profile->email, $profile->full_name, $job->title, $controlNumber, $qsRows, $remarks, $isExcluded);
        } else {
            $missingMandatory = [];

            if ($isExcluded) {
                $checkedMandatory = json_decode($evaluation->documentary_mandatory ?? '[]', true) ?: [];
                foreach (eval_mandatory_requirements() as $key => $label) {
                    if (!in_array($key, $checkedMandatory, true)) {
                        $missingMandatory[] = preg_replace('/^.*?\]\s*/', '', $label);
                    }
                }
            }

            $emailSent = send_disqualified($profile->email, $profile->full_name, $job->title, $controlNumber, $qsRows, $isExcluded, $missingMandatory, $remarks);
        }
    }
} catch (\Throwable $e) {
    $emailSent = false;
    error_log('[admin-portal] application-status email failed: '.$e->getMessage());
}

// The status is saved either way; just don't let the admin think the
// applicant was told when the email didn't go out.
if ($emailSent === false) {
    flash_set('error', 'Application marked '.ucfirst($newStatus).', but the email to the applicant could not be sent. Please inform them another way.');
} else {
    flash_set('success', 'Application marked '.ucfirst($newStatus).'.');
}
header('Location: '.$referer);
exit;
