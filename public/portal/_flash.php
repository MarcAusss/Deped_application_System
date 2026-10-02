<?php

if (!defined('PORTAL_BOOTED')) {
    http_response_code(404);
    exit;
}

/**
 * Session-flash helpers mirroring Laravel's ->with('key', 'value') /
 * session('key') / portal_old('field') pattern, hand-rolled for this portal.
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

/**
 * Call once per request, as early as possible (from _boot.php), so a flash
 * set on the previous request is readable on this one and then discarded.
 */
function flash_rotate(): void
{
    $_SESSION['_flash_prev'] = $_SESSION['_flash'] ?? [];
    $_SESSION['_flash'] = [];
}

function portal_old(string $field, mixed $default = ''): mixed
{
    $input = $_SESSION['_flash_prev']['_old_input'] ?? [];

    return $input[$field] ?? $default;
}

/**
 * Like portal_old(), but for repeatable-row arrays (education[], experience[]...).
 * Returns null (not an empty array) when there's no prior failed submission,
 * so callers can tell "no old data" apart from "old data was an empty array".
 */
function old_array(string $field): ?array
{
    $input = $_SESSION['_flash_prev']['_old_input'] ?? [];

    return isset($input[$field]) && is_array($input[$field]) ? $input[$field] : null;
}

function set_old_input(array $input): void
{
    // Never persist raw uploaded file objects into the session.
    unset($input['_csrf_token']);

    foreach ($input as $key => $value) {
        if ($value instanceof \stdClass || is_array($value) && isset($value['tmp_name'])) {
            unset($input[$key]);
        }
    }

    flash_set('_old_input', $input);
}

/**
 * @return array<string, string>
 */
function validation_errors(): array
{
    return $_SESSION['_flash_prev']['_errors'] ?? [];
}

function validation_error(string $field): ?string
{
    return validation_errors()[$field] ?? null;
}
