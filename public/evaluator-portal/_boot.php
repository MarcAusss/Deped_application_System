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

// Matches config/app.php's former 'timezone' => 'Asia/Manila' — without this,
// PHP defaults to UTC and every date()/time()/strtotime() call (greetings,
// deadline checks, displayed dates) is 8 hours off from real local time.
date_default_timezone_set('Asia/Manila');

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

/*
 * Production-safe error handling: never leak raw PHP errors/stack traces to
 * evaluators. Gated by APP_DEBUG (the same variable Laravel reads), so
 * local dev still sees real errors while a live server shows a friendly
 * page and logs the real detail to storage/logs/laravel.log instead.
 */
$portalDebug = evaluator_env('APP_DEBUG', false) === true;

error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('error_log', PORTAL_ROOT.'/storage/logs/laravel.log');
ini_set('display_errors', $portalDebug ? '1' : '0');

if (!$portalDebug) {
    set_exception_handler(function (\Throwable $e) {
        error_log('[evaluator-portal] Uncaught: '.$e);
        require __DIR__.'/_error.php';
        exit;
    });

    register_shutdown_function(function () {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            error_log('[evaluator-portal] Fatal: '.$error['message'].' in '.$error['file'].':'.$error['line']);
            if (!headers_sent()) {
                require __DIR__.'/_error.php';
            }
        }
    });
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
require PORTAL_ROOT.'/public/portal/_documents.php';

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

/**
 * Mirrors ApplicationResource::isPastApplicationDeadline() — "application
 * period has ended" means either explicitly closed (is_open = false) or its
 * deadline has passed. A closed position with no deadline ever set must
 * still count as ended, not stay open forever just because there's no date
 * to compare against.
 */
function eval_application_period_ended(?object $job): bool
{
    if (!$job) {
        return true;
    }

    if (!$job->is_open) {
        return true;
    }

    if (empty($job->until)) {
        return false;
    }

    $deadline = strtotime($job->until.' '.($job->until_time ?: '23:59:59'));

    return time() > $deadline;
}

/**
 * Groups a flat, already-sorted list of application rows (each must have a
 * ->job_title property) into position buckets, sorted alphabetically by
 * position title — mirrors admin-portal's admin_group_by_position().
 *
 * @param array<int, object> $applications
 * @return array<string, array<int, object>>
 */
function eval_group_by_position(array $applications): array
{
    $groups = [];

    foreach ($applications as $app) {
        $title = $app->job_title ?? 'Unassigned Position';
        $groups[$title][] = $app;
    }

    ksort($groups, SORT_NATURAL | SORT_FLAG_CASE);

    return $groups;
}

/**
 * Heroicons outline paths (24x24, stroke-width 1.5), keyed by name — used by
 * evaluator_section_header() for the colored section-header bars on
 * view.php and evaluate.php.
 */
function evaluator_section_icon(string $name): string
{
    $paths = [
        'briefcase' => 'M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0',
        'hashtag' => 'M5.25 8.25h15m-16.5 7.5h15m-1.8-13.5-3.9 19.5m-2.1-19.5-3.9 19.5',
        'clipboard' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.03 0 1.9.693 2.166 1.638m-5.8 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664M6.75 7.5h10.5a2.25 2.25 0 0 1 2.25 2.25v10.5a2.25 2.25 0 0 1-2.25 2.25H6.75a2.25 2.25 0 0 1-2.25-2.25V9.75A2.25 2.25 0 0 1 6.75 7.5Z',
        'scale' => 'M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971Zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971Z',
        'user' => 'M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
        'flag' => 'M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5',
        'academic-cap' => 'M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5',
        'building' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21',
        'presentation' => 'M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5',
        'id-card' => 'M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z',
        'paperclip' => 'm18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.5L8.552 18.448a1.5 1.5 0 0 1-2.122-2.122l9.546-9.546',
    ];

    return $paths[$name] ?? '';
}

/**
 * Renders a full-width colored header bar (icon + title + optional
 * subtitle) for the top of a section card — mirrors the source system's
 * Filament Infolist section headers.
 */
function evaluator_section_header(string $title, string $icon, ?string $subtitle = null, string $actionsHtml = ''): string
{
    $sub = $subtitle ? '<p class="mt-0.5 text-xs text-indigo-200">'.evaluator_e($subtitle).'</p>' : '';
    $actions = $actionsHtml ? '<div class="ml-auto flex-shrink-0">'.$actionsHtml.'</div>' : '';

    return '<div class="flex items-center gap-3 bg-evaluator-indigoDark px-6 py-4">'
        .'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6 flex-shrink-0 text-white"><path stroke-linecap="round" stroke-linejoin="round" d="'.evaluator_section_icon($icon).'" /></svg>'
        .'<div><h3 class="text-lg font-black text-white">'.evaluator_e($title).'</h3>'.$sub.'</div>'
        .$actions
        .'</div>';
}

/**
 * Renders one requirement row for the Documentary Requirements checklist —
 * a green bordered card with a check icon when selected, a plain gray box
 * when not. Used by both the read-only view (view.php) and, as the visual
 * basis for the clickable version, evaluate.php.
 */
function evaluator_requirement_box(string $label, bool $checked): string
{
    $box = $checked
        ? '<span class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded bg-emerald-600 text-white"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="h-3 w-3"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg></span>'
        : '<span class="mt-0.5 h-5 w-5 flex-shrink-0 rounded border border-slate-300 bg-white"></span>';
    $boxClass = $checked ? 'border-emerald-300 bg-emerald-50' : 'border-slate-200';
    $textClass = $checked ? 'font-bold text-indigo-700' : 'text-slate-500';

    return '<div class="flex items-start gap-2 rounded-lg border '.$boxClass.' px-3 py-2.5 text-sm">'.$box.'<span class="'.$textClass.'">'.evaluator_e($label).'</span></div>';
}
