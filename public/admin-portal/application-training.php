<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

admin_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
csrf_verify();

$pdo = portal_pdo();
$applicationId = (int) ($_POST['application_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$applicationId) {
    http_response_code(400);
    exit;
}

$redirect = admin_url('application-view.php?id='.$applicationId);

if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    $pdo->prepare('DELETE FROM applicant_trainings WHERE id = ? AND application_id = ?')->execute([$id, $applicationId]);
    flash_set('success', 'Training record deleted.');
    header('Location: '.$redirect);
    exit;
}

$title = trim($_POST['title'] ?? '');
$hours = trim($_POST['hours'] ?? '');
$trainingDate = trim($_POST['training_date'] ?? '');
$trainingEndDate = trim($_POST['training_end_date'] ?? '');

if ($title === '') {
    flash_set('error', 'Training / Seminar Title is required.');
    header('Location: '.$redirect);
    exit;
}

// Mirrors TrainingRelationManager's month-picker dehydration: "Y-m" -> "Y-m-01".
$normalizeMonth = function (string $value): ?string {
    if ($value === '') {
        return null;
    }
    if (!preg_match('/^\d{4}-\d{2}$/', $value)) {
        return null;
    }

    return $value.'-01';
};

$data = [
    'title' => $title,
    'hours' => $hours !== '' ? (int) $hours : null,
    'training_date' => $normalizeMonth($trainingDate),
    'training_end_date' => $normalizeMonth($trainingEndDate),
];

if ($action === 'create') {
    $pdo->prepare(
        'INSERT INTO applicant_trainings (application_id, title, hours, training_date, training_end_date, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, NOW(), NOW())'
    )->execute([$applicationId, $data['title'], $data['hours'], $data['training_date'], $data['training_end_date']]);
    flash_set('success', 'Training record added.');
} elseif ($action === 'update') {
    $id = (int) ($_POST['id'] ?? 0);
    $pdo->prepare(
        'UPDATE applicant_trainings SET title = ?, hours = ?, training_date = ?, training_end_date = ?, updated_at = NOW()
         WHERE id = ? AND application_id = ?'
    )->execute([$data['title'], $data['hours'], $data['training_date'], $data['training_end_date'], $id, $applicationId]);
    flash_set('success', 'Training record updated.');
} else {
    http_response_code(400);
    exit;
}

header('Location: '.$redirect);
exit;
