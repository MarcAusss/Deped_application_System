<?php

/**
 * Shared bootstrap for public/admin-portal/ — the hand-rolled pure-PHP
 * Admin portal (Phase 3 of the pure-PHP rewrite). Mirrors
 * public/portal/_boot.php and public/evaluator-portal/_boot.php's pattern
 * exactly, but with its own session name and its own admin_*-prefixed
 * helpers, so this portal shares zero session/cookie state with Laravel's
 * admin login or the Phase 1/2 portals. Every real page must define
 * PORTAL_BOOTED before requiring this file or any other _*.php helper.
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
 * Own .env reader, named distinctly from the other phases' env() readers.
 * Collision-checked against Laravel's own global env() helper, which gets
 * loaded into memory as a side effect of requiring vendor/autoload.php.
 */
function admin_env(string $key, mixed $default = null): mixed
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

session_name('depedcares_admin_sess');
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
    return admin_env($key, $default);
}

// Reuse Phase 1's fully generic, stateless primitives directly.
require PORTAL_ROOT.'/public/portal/_db.php';
require PORTAL_ROOT.'/public/portal/_validate.php';

// Reuse Phase 2's stateless port of EvaluationChecklist/IerApplicationFormatter
// directly — every eval_*() function operates on plain PDO row arrays with
// no session coupling, so it's safe to share across portals (each request
// only ever boots one portal's chain, so there's no redeclare risk).
require PORTAL_ROOT.'/public/evaluator-portal/_evaluation.php';

// Own copies of the session-coupled helpers, for full independence.
require __DIR__.'/_csrf.php';
require __DIR__.'/_flash.php';
require __DIR__.'/_upload.php';

flash_rotate();

/**
 * The logged-in admin's row, fetched once per request and cached.
 */
function admin_user(): ?object
{
    static $cached = false;

    if ($cached !== false) {
        return $cached ?: null;
    }

    if (empty($_SESSION['admin_user_id'])) {
        $cached = null;

        return null;
    }

    $stmt = portal_pdo()->prepare('SELECT id, name, email, role, is_approved FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['admin_user_id']]);
    $user = $stmt->fetch();

    // Re-check role on every request, not just at login time — matches
    // User::canAccessPanel()'s admin-panel gate exactly: role === 'admin'
    // only, is_approved is NOT checked for the admin panel (that column only
    // gates the evaluator panel).
    if ($user && $user->role !== 'admin') {
        $user = null;
    }

    $cached = $user ?: null;

    return $cached;
}

function admin_login(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['admin_user_id'] = $userId;
}

function admin_logout(): void
{
    $_SESSION = [];
    session_destroy();
}

function admin_require_login(): void
{
    if (admin_user() === null) {
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '/admin-portal/dashboard.php');
        header('Location: /admin-portal/login.php?redirect='.$redirect);
        exit;
    }
}

function admin_url(string $path): string
{
    return '/admin-portal/'.ltrim($path, '/');
}

function admin_e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Lets "12 midnight" / "12 noon" (or plain "midnight" / "noon") be entered as
 * unambiguous alternatives to 12:00 AM / 12:00 PM. Ported verbatim from
 * JobPositionResource::normalizeClosingTime().
 */
function admin_normalize_closing_time(string $value): string
{
    $normalized = preg_replace('/^12\s+(midnight|noon)$/i', '$1', trim($value));

    return $normalized ?? $value;
}

/**
 * Sequential JP-0001 style number, assigned only the first time a position
 * is posted. Ported verbatim from JobPosition::generateJpNumber().
 */
function admin_generate_jp_number(): string
{
    $pdo = portal_pdo();
    $next = (int) $pdo->query("SELECT COUNT(*) FROM job_positions WHERE jp_number IS NOT NULL")->fetchColumn() + 1;

    do {
        $candidate = 'JP-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        $next++;

        $stmt = $pdo->prepare('SELECT id FROM job_positions WHERE jp_number = ?');
        $stmt->execute([$candidate]);
    } while ($stmt->fetch());

    return $candidate;
}

/** Ported from JobPosition::hasDeadlinePassed(). */
function admin_job_deadline_passed(?string $until, ?string $untilTime): bool
{
    if (!$until) {
        return false;
    }
    $deadline = strtotime($until.' '.($untilTime ?: '23:59:59'));

    return $deadline !== false && time() > $deadline;
}

/** Mirrors DocumentsRelationManager's `type` Select options exactly. */
function admin_document_types(): array
{
    return [
        'Resume' => 'Resume',
        'Diploma' => 'Diploma',
        'Transcript' => 'Transcript of Records',
        'Certificate' => 'Certificate',
        'Government ID' => 'Government ID',
        'Other' => 'Other',
    ];
}

/**
 * Up to 2 initials from a full name, e.g. "Cedric Domanais" -> "CD".
 */
function admin_initials(string $name): string
{
    $parts = array_filter(explode(' ', trim($name)), fn ($p) => $p !== '');
    $initials = array_map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice($parts, 0, 2));

    return implode('', $initials);
}
