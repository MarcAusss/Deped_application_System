<?php

/**
 * Shared bootstrap for public/evaluator-portal/ — the hand-rolled pure-PHP
 * Evaluator portal (Phase 2 of the pure-PHP rewrite). Mirrors
 * public/portal/_boot.php's pattern exactly, but with its own session name
 * and its own evaluator_*-prefixed helpers, so this portal shares zero
 * session/cookie state with Laravel's admin login or the Phase 1 applicant
 * portal. Every real page must define PORTAL_BOOTED before requiring this
 * file or any other _*.php helper.
 */

if (!defined('PORTAL_BOOTED')) {
    http_response_code(404);
    exit;
}

define('PORTAL_ROOT', dirname(__DIR__, 2));

require PORTAL_ROOT.'/vendor/autoload.php';

if (!isset($_ENV['APP_KEY']) && !getenv('APP_KEY')) {
    $dotenv = Dotenv\Dotenv::createImmutable(PORTAL_ROOT);
    $dotenv->safeLoad();
}

/**
 * Own .env reader, named distinctly from Phase 1's portal_env() so both
 * _boot.php files could theoretically be required in the same process
 * without a redeclare collision (not expected to happen, but cheap safety).
 * Also collision-checked against Laravel's own global env() helper, which
 * gets loaded into memory as a side effect of requiring vendor/autoload.php.
 */
function evaluator_env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null) {
        return $default;
    }

    return match (strtolower((string) $value)) {
        'true', '(true)' => true,
        'false', '(false)' => false,
        'null', '(null)' => null,
        'empty', '(empty)' => '',
        default => $value,
    };
}

session_name('depedcares_evaluator_sess');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => (($_SERVER['HTTPS'] ?? '') !== '') || (($_SERVER['SERVER_PORT'] ?? '') === '443'),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// Phase 1's _db.php internally calls portal_env() (defined in Phase 1's own
// _boot.php, which this file deliberately does not require). Alias it here
// so _db.php's DB_* lookups resolve, without pulling in Phase 1's session
// logic.
function portal_env(string $key, mixed $default = null): mixed
{
    return evaluator_env($key, $default);
}

// Reuse Phase 1's fully generic, stateless primitives directly.
require PORTAL_ROOT.'/public/portal/_db.php';
require PORTAL_ROOT.'/public/portal/_validate.php';

// Own copies of the session-coupled helpers, for full independence.
require __DIR__.'/_csrf.php';
require __DIR__.'/_flash.php';
require __DIR__.'/_evaluation.php';

flash_rotate();

/**
 * The logged-in evaluator's row, fetched once per request and cached.
 */
function evaluator_user(): ?object
{
    static $cached = false;

    if ($cached !== false) {
        return $cached ?: null;
    }

    if (empty($_SESSION['evaluator_user_id'])) {
        $cached = null;

        return null;
    }

    $stmt = portal_pdo()->prepare('SELECT id, name, email, role, is_approved FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['evaluator_user_id']]);
    $user = $stmt->fetch();

    // Re-check role/approval on every request, not just at login time — if an
    // admin revokes approval or changes the role after the session started,
    // access should stop immediately rather than persisting until logout.
    if ($user && ($user->role !== 'evaluator' || !(int) $user->is_approved)) {
        $user = null;
    }

    $cached = $user ?: null;

    return $cached;
}

function evaluator_login(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['evaluator_user_id'] = $userId;
}

function evaluator_logout(): void
{
    $_SESSION = [];
    session_destroy();
}

function evaluator_require_login(): void
{
    if (evaluator_user() === null) {
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '/evaluator-portal/applications.php');
        header('Location: /evaluator-portal/login.php?redirect='.$redirect);
        exit;
    }
}

function evaluator_url(string $path): string
{
    return '/evaluator-portal/'.ltrim($path, '/');
}

function evaluator_e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Up to 2 initials from a full name, e.g. "Cedric Domanais" -> "CD".
 */
function evaluator_initials(string $name): string
{
    $parts = array_filter(explode(' ', trim($name)), fn ($p) => $p !== '');
    $initials = array_map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice($parts, 0, 2));

    return implode('', $initials);
}
