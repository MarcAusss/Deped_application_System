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

try {
    portal_pdo()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    flash_set('success', 'User deleted.');
} catch (\PDOException $e) {
    // 23000 = integrity constraint violation (foreign key) — the account has
    // related activity elsewhere and can't be hard-deleted without losing it.
    if ($e->getCode() === '23000') {
        $message = match (true) {
            str_contains($e->getMessage(), 'application_evaluations_evaluator_id_foreign')
                => 'This user cannot be deleted because the account has evaluated an applicant.',
            str_contains($e->getMessage(), 'application_status_logs_changed_by_foreign')
                => 'This user cannot be deleted because the account has recorded application status changes.',
            str_contains($e->getMessage(), 'application_control_numbers_generated_by_foreign')
                => 'This user cannot be deleted because the account has generated application control numbers.',
            default => 'This user cannot be deleted because the account is still referenced elsewhere in the system.',
        };
        flash_set('error', $message);
    } else {
        error_log('[admin-portal] user-delete.php failed: '.$e->getMessage());
        flash_set('error', 'The user could not be deleted. Please try again.');
    }
}

header('Location: '.admin_url('users.php'));
exit;
