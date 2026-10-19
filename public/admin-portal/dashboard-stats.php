<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

admin_require_login();

$pdo = portal_pdo();

$pendingCount = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'")->fetchColumn();
// Matches dashboard.php's own $approvalCount — "For Final Action" counts
// both 'evaluated' and 'excluded', same as $canDecide elsewhere.
$approvalCount = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status IN ('evaluated', 'excluded')")->fetchColumn();

header('Content-Type: application/json');
echo json_encode([
    'pending' => $pendingCount,
    'approval' => $approvalCount,
]);
