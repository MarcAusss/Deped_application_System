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

$levels = ['Elementary', 'High School', 'Vocational', 'College', 'Post Graduate', "Other's"];

if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    $pdo->prepare('DELETE FROM applicant_educations WHERE id = ? AND application_id = ?')->execute([$id, $applicationId]);
    flash_set('success', 'Education record deleted.');
    header('Location: '.$redirect);
    exit;
}

$level = $_POST['level'] ?? '';
$levelSpecify = trim($_POST['level_specify'] ?? '');
$school = trim($_POST['school'] ?? '');
$degree = trim($_POST['degree'] ?? '');
$yearGraduated = trim($_POST['year_graduated'] ?? '');

if (!in_array($level, $levels, true)) {
    flash_set('error', 'Please select a valid education level.');
    header('Location: '.$redirect);
    exit;
}
if ($level === "Other's" && $levelSpecify === '') {
    flash_set('error', 'Please specify the education level.');
    header('Location: '.$redirect);
    exit;
}

$data = [
    'level' => $level,
    'level_specify' => $level === "Other's" ? ($levelSpecify ?: null) : null,
    'school' => $school ?: null,
    'degree' => $degree ?: null,
    'year_graduated' => $yearGraduated ?: null,
];

if ($action === 'create') {
    $pdo->prepare(
        'INSERT INTO applicant_educations (application_id, level, level_specify, school, degree, year_graduated, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())'
    )->execute([$applicationId, $data['level'], $data['level_specify'], $data['school'], $data['degree'], $data['year_graduated']]);
    flash_set('success', 'Education record added.');
} elseif ($action === 'update') {
    $id = (int) ($_POST['id'] ?? 0);
    $pdo->prepare(
        'UPDATE applicant_educations SET level = ?, level_specify = ?, school = ?, degree = ?, year_graduated = ?, updated_at = NOW()
         WHERE id = ? AND application_id = ?'
    )->execute([$data['level'], $data['level_specify'], $data['school'], $data['degree'], $data['year_graduated'], $id, $applicationId]);
    flash_set('success', 'Education record updated.');
} else {
    http_response_code(400);
    exit;
}

header('Location: '.$redirect);
exit;
