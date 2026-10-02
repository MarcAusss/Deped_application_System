<?php

if (!defined('PORTAL_BOOTED')) {
    http_response_code(404);
    exit;
}

function admin_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function admin_csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="'.admin_e(admin_csrf_token()).'">';
}

/**
 * Call at the top of every POST handler. Exits with 403 on mismatch.
 */
function csrf_verify(): void
{
    $submitted = $_POST['_csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $submitted)) {
        http_response_code(403);
        echo 'Your session has expired. Please go back and try again.';
        exit;
    }
}
