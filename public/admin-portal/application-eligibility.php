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
    $pdo->prepare('DELETE FROM applicant_eligibilities WHERE id = ? AND application_id = ?')->execute([$id, $applicationId]);
    flash_set('success', 'Eligibility record deleted.');
    header('Location: '.$redirect);
    exit;
}

$licenseNames = ['CS Sub-Professional', 'CSCS Professional', 'RA1080', "Other's"];

$licenseName = $_POST['license_name'] ?? '';
$licenseSpecify = trim($_POST['license_specify'] ?? '');
$rating = trim($_POST['rating'] ?? '');
$dateIssued = trim($_POST['date_issued'] ?? '');
$validUntil = trim($_POST['valid_until'] ?? '');
$neverExpires = isset($_POST['never_expires']) && $_POST['never_expires'] === '1';

if (!in_array($licenseName, $licenseNames, true)) {
    flash_set('error', 'Please select a valid license / eligibility name.');
    header('Location: '.$redirect);
    exit;
}
$needsSpecify = in_array($licenseName, ['RA1080', "Other's"], true);
if ($needsSpecify && $licenseSpecify === '') {
    flash_set('error', 'Please specify the license / eligibility.');
    header('Location: '.$redirect);
    exit;
}

$data = [
    'license_name' => $licenseName,
    'license_specify' => $needsSpecify ? ($licenseSpecify ?: null) : null,
    'rating' => $rating ?: null,
    'date_issued' => $dateIssued ?: null,
    'valid_until' => $neverExpires ? null : ($validUntil ?: null),
    'never_expires' => $neverExpires ? 1 : 0,
];

if ($action === 'create') {
    $pdo->prepare(
        'INSERT INTO applicant_eligibilities (application_id, license_name, license_specify, rating, date_issued, valid_until, never_expires, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
    )->execute([$applicationId, $data['license_name'], $data['license_specify'], $data['rating'], $data['date_issued'], $data['valid_until'], $data['never_expires']]);
    flash_set('success', 'Eligibility record added.');
} elseif ($action === 'update') {
    $id = (int) ($_POST['id'] ?? 0);
    $pdo->prepare(
        'UPDATE applicant_eligibilities SET license_name = ?, license_specify = ?, rating = ?, date_issued = ?, valid_until = ?, never_expires = ?, updated_at = NOW()
         WHERE id = ? AND application_id = ?'
    )->execute([$data['license_name'], $data['license_specify'], $data['rating'], $data['date_issued'], $data['valid_until'], $data['never_expires'], $id, $applicationId]);
    flash_set('success', 'Eligibility record updated.');
} else {
    http_response_code(400);
    exit;
}

header('Location: '.$redirect);
exit;
