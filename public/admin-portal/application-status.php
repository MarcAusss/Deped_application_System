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

$stmt = $pdo->prepare('SELECT id, status FROM applications WHERE id = ?');
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

flash_set('success', 'Application marked '.ucfirst($newStatus).'.');
header('Location: '.$referer);
exit;
