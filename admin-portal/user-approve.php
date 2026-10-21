<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

admin_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    exit;
}

$pdo = portal_pdo();
$pdo->prepare("UPDATE users SET is_approved = 1, updated_at = NOW() WHERE id = ? AND role = 'evaluator'")->execute([$id]);

flash_set('success', 'User approved.');
header('Location: '.admin_url('users.php'));
exit;
