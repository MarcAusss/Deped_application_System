<?php

if (!defined('PORTAL_BOOTED')) {
    http_response_code(404);
    exit;
}

/**
 * Session-flash helpers, mirroring Phase 1/2's _flash.php.
 */
function flash_set(string $key, mixed $value): void
{
    $_SESSION['_flash'][$key] = $value;
}

function flash_get(string $key, mixed $default = null): mixed
{
    if (!array_key_exists($key, $_SESSION['_flash_prev'] ?? [])) {
        return $default;
    }

    return $_SESSION['_flash_prev'][$key];
}

function flash_rotate(): void
{
    $_SESSION['_flash_prev'] = $_SESSION['_flash'] ?? [];
    $_SESSION['_flash'] = [];
}

function admin_old(string $field, mixed $default = ''): mixed
{
    $input = $_SESSION['_flash_prev']['_old_input'] ?? [];

    return $input[$field] ?? $default;
}

function set_old_input(array $input): void
{
    unset($input['_csrf_token']);
    flash_set('_old_input', $input);
}

/** @return array<string, string> */
function validation_errors(): array
{
    return $_SESSION['_flash_prev']['_errors'] ?? [];
}

// Note: the `Errors` class itself is NOT redefined here — it's already
// loaded via the shared require of public/portal/_validate.php in
// _boot.php, and redeclaring a class in the same request is a fatal error.
