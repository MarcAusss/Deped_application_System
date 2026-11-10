<?php

if (!defined('PORTAL_BOOTED')) {
    http_response_code(404);
    exit;
}

function portal_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function portal_csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="'.portal_e(portal_csrf_token()).'">';
}

/**
 * Call at the top of every POST handler.
 *
 * On a token mismatch (stale tab, long idle time, etc.) this used to show a
 * dead-end "Session Expired" page — jarring for an applicant mid-form, and
 * the single most common complaint about this portal. Instead, it now
 * auto-recovers: issue a fresh token, keep whatever was typed, and bounce
 * back to the same form (GET, so the applicant just reviews and submits
 * again) rather than exiting. The underlying protection is unchanged — a
 * genuinely forged cross-site request still can't supply a valid token —
 * this only removes the dead end for the applicant's own stale/expired one.
 */
function csrf_verify(): void
{
    $submitted = $_POST['_csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $submitted)) {
        unset($_SESSION['csrf_token']);
        set_old_input($_POST);
        // Set on both flash channels: pages that render flash_get('error')
        // (apply.php, profile.php) and pages that render $errors->all()/
        // ->has() (login.php, register.php, reset-password.php) each pick
        // up whichever one they actually read.
        $notice = 'Your form session was refreshed for security. Please review your entries and submit again.';
        flash_set('error', $notice);
        flash_set('_errors', ['_csrf' => $notice]);
        header('Location: '.($_SERVER['REQUEST_URI'] ?? portal_url('dashboard.php')));
        exit;
    }
}
