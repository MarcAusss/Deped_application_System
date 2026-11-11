<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

// Main admin only — this fully releases whatever an evaluator already
// checked/wrote, so it needs a higher bar than the regular Restore action
// (which keeps the evaluation data and only reopens a qualified/disqualified
// decision back to Evaluated).
admin_require_main_account();
$admin = admin_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
$referer = $_SERVER['HTTP_REFERER'] ?? admin_url('applications.php');

if (!$id) {
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

// Final decisions (Qualified/Disqualified) aren't touched by this action —
// those go through Restore instead, which keeps the evaluation data intact.
if (in_array($application->status, ['qualified', 'disqualified'], true)) {
    flash_set('error', 'A finalized (Qualified/Disqualified) application cannot have its claim undone this way.');
    header('Location: '.$referer);
    exit;
}

// The real thing being undone is the claim itself — evaluator_id can get
// set from just a *partial* save (e.g. documentary requirements checked but
// the checklist not yet complete), which never moves applications.status
// off 'pending'. So the gate is "is there a claim at all", not the
// application's status.
$stmt = $pdo->prepare('SELECT evaluator_id FROM application_evaluations WHERE application_id = ?');
$stmt->execute([$id]);
$claimedBy = $stmt->fetchColumn();

if (!$claimedBy) {
    flash_set('error', 'This application has no evaluator claim to undo.');
    header('Location: '.$referer);
    exit;
}

// Deliberately deletes the evaluation row rather than just clearing
// evaluator_id: an accidental claim can come with an already-filled-in
// checklist/remarks that belong to the wrong evaluator, so "undo claim"
// means truly fresh — not just unlocked with someone else's answers
// still sitting in it.
$pdo->prepare('DELETE FROM application_evaluations WHERE application_id = ?')->execute([$id]);
if ($application->status !== 'pending') {
    $pdo->prepare("UPDATE applications SET status = 'pending', updated_at = NOW() WHERE id = ?")->execute([$id]);
    $pdo->prepare('INSERT INTO application_status_logs (application_id, status, changed_by, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())')
        ->execute([$id, 'pending', $admin->id]);
}

flash_set('success', 'Evaluator claim undone. This application is pending again and open to any evaluator.');
header('Location: '.$referer);
exit;
