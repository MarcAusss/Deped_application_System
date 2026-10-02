<?php

if (!defined('PORTAL_BOOTED')) {
    http_response_code(404);
    exit;
}

/**
 * Small validation primitives, used directly by each page rather than a
 * generic rule-string parser — keeps each page's validation readable and
 * lets every message match the existing Laravel app's wording exactly.
 */

function v_present(mixed $value): bool
{
    return $value !== null && $value !== '';
}

function v_email(string $value): bool
{
    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
}

function v_max(string $value, int $max): bool
{
    return mb_strlen($value) <= $max;
}

function v_min(string $value, int $min): bool
{
    return mb_strlen($value) >= $min;
}

function v_date(string $value): bool
{
    return strtotime($value) !== false;
}

function v_date_before_or_equal_today(string $value): bool
{
    $ts = strtotime($value);

    return $ts !== false && $ts <= strtotime('today 23:59:59');
}

function v_date_format_ym(string $value): bool
{
    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value)) {
        return false;
    }

    return strtotime($value.'-01') <= strtotime(date('Y-m').'-01');
}

/**
 * Simple field => message accumulator, mirroring the shape of Laravel's
 * $errors bag closely enough for the views (`$errors['field']`).
 */
class Errors
{
    /** @var array<string, string> */
    private array $messages = [];

    public function add(string $field, string $message): void
    {
        // First error per field wins, matching $errors->first($field).
        if (!isset($this->messages[$field])) {
            $this->messages[$field] = $message;
        }
    }

    public function has(string $field): bool
    {
        return isset($this->messages[$field]);
    }

    public function first(string $field): ?string
    {
        return $this->messages[$field] ?? null;
    }

    public function any(): bool
    {
        return $this->messages !== [];
    }

    /** @return array<string, string> */
    public function all(): array
    {
        return $this->messages;
    }
}
