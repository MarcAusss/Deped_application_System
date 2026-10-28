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
 *
 * AJAX submissions (marked with a hidden ajax=1 field, same convention the
 * handler itself uses to decide between a JSON vs. redirect response) get a
 * JSON 403 instead of the full HTML page — otherwise fetch().then(res =>
 * res.json()) throws on the HTML body and the UI just shows a generic
 * "Something went wrong" with no indication the session/token expired.
 */
function csrf_verify(): void
{
    $submitted = $_POST['_csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $submitted)) {
        http_response_code(403);

        if (($_POST['ajax'] ?? '') === '1') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'errors' => ['_csrf' => 'Your session has expired. Please refresh the page and try again.']]);
            exit;
        }

        require __DIR__.'/_csrf_expired.php';
        exit;
    }
}
