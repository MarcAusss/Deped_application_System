<?php

/**
 * Shared bootstrap for every page under public/portal/ — the hand-rolled
 * pure-PHP applicant portal (Phase 1 of the pure-PHP rewrite). Every real
 * page must define PORTAL_BOOTED before requiring this file or any other
 * _*.php helper, and every _*.php helper must refuse to run standalone.
 *
 * This never touches Laravel's own bootstrap/app.php — it only reuses the
 * already-vendored Composer autoloader (for phpdotenv + PHPMailer) and reads
 * the same .env file Laravel reads, independently.
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
 * Minimal .env reader — deliberately not Laravel's portal_env() helper, so this
 * portal has zero dependency on Illuminate\Support being loaded.
 */
function portal_env(string $key, mixed $default = null): mixed
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

session_name('depedcares_portal_sess');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => (($_SERVER['HTTPS'] ?? '') !== '') || (($_SERVER['SERVER_PORT'] ?? '') === '443'),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require __DIR__.'/_db.php';
require __DIR__.'/_csrf.php';
require __DIR__.'/_flash.php';
require __DIR__.'/_validate.php';
require __DIR__.'/_mailer.php';

flash_rotate();

/**
 * The logged-in applicant's row, fetched once per request and cached.
 */
function portal_user(): ?object
{
    static $cached = false;

    if ($cached !== false) {
        return $cached ?: null;
    }

    if (empty($_SESSION['applicant_id'])) {
        $cached = null;

        return null;
    }

    $stmt = portal_pdo()->prepare('SELECT id, name, email FROM applicants WHERE id = ?');
    $stmt->execute([$_SESSION['applicant_id']]);
    $applicant = $stmt->fetch();

    $cached = $applicant ?: null;

    return $cached;
}

function portal_login(int $applicantId): void
{
    session_regenerate_id(true);
    $_SESSION['applicant_id'] = $applicantId;
}

function portal_logout(): void
{
    $_SESSION = [];
    session_destroy();
}

/**
 * Redirect to login (preserving where the visitor was headed) if not
 * logged in. Call at the top of any page that requires auth.
 */
function portal_require_login(): void
{
    if (portal_user() === null) {
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '/portal/dashboard.php');
        header('Location: /portal/login.php?redirect='.$redirect);
        exit;
    }
}

function portal_url(string $path): string
{
    return '/portal/'.ltrim($path, '/');
}

function portal_e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Up to 2 initials from a full name, e.g. "Cedric Domanais" -> "CD".
 */
function collect_initials(string $name): string
{
    $parts = array_filter(explode(' ', trim($name)), fn ($p) => $p !== '');
    $initials = array_map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice($parts, 0, 2));

    return implode('', $initials);
}
