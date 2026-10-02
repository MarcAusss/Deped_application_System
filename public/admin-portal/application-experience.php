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
    $pdo->prepare('DELETE FROM applicant_experiences WHERE id = ? AND application_id = ?')->execute([$id, $applicationId]);
    flash_set('success', 'Experience record deleted.');
    header('Location: '.$redirect);
    exit;
}

$title = trim($_POST['title'] ?? '');
$company = trim($_POST['company'] ?? '');
$firstDay = trim($_POST['first_day'] ?? '');
$lastDay = trim($_POST['last_day'] ?? '');
$yearsMonths = trim($_POST['years_months'] ?? '');
$details = trim($_POST['details'] ?? '');

if ($title === '') {
    flash_set('error', 'Job Title is required.');
    header('Location: '.$redirect);
    exit;
}

$data = [
    'title' => $title,
    'company' => $company ?: null,
    'first_day' => $firstDay ?: null,
    'last_day' => $lastDay ?: null,
    'years_months' => $yearsMonths ?: null,
    'details' => $details ?: null,
];

if ($action === 'create') {
    $pdo->prepare(
        'INSERT INTO applicant_experiences (application_id, title, company, first_day, last_day, years_months, details, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
    )->execute([$applicationId, $data['title'], $data['company'], $data['first_day'], $data['last_day'], $data['years_months'], $data['details']]);
    flash_set('success', 'Experience record added.');
} elseif ($action === 'update') {
    $id = (int) ($_POST['id'] ?? 0);
    $pdo->prepare(
        'UPDATE applicant_experiences SET title = ?, company = ?, first_day = ?, last_day = ?, years_months = ?, details = ?, updated_at = NOW()
         WHERE id = ? AND application_id = ?'
    )->execute([$data['title'], $data['company'], $data['first_day'], $data['last_day'], $data['years_months'], $data['details'], $id, $applicationId]);
    flash_set('success', 'Experience record updated.');
} else {
    http_response_code(400);
    exit;
}

header('Location: '.$redirect);
exit;
