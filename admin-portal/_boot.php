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

define('PORTAL_ROOT', dirname(__DIR__));

require PORTAL_ROOT.'/_base_path.php';
define('BASE_PATH', app_base_path(1));

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

/*
 * Production-safe error handling: never leak raw PHP errors/stack traces to
 * office staff. Gated by APP_DEBUG (the same variable Laravel reads), so
 * local dev still sees real errors while a live server shows a friendly
 * page and logs the real detail to storage/logs/laravel.log instead.
 */
$portalDebug = admin_env('APP_DEBUG', false) === true;

error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('error_log', PORTAL_ROOT.'/storage/logs/laravel.log');
ini_set('display_errors', $portalDebug ? '1' : '0');

if (!$portalDebug) {
    set_exception_handler(function (\Throwable $e) {
        error_log('[admin-portal] Uncaught: '.$e);
        require __DIR__.'/_error.php';
        exit;
    });

    register_shutdown_function(function () {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            error_log('[admin-portal] Fatal: '.$error['message'].' in '.$error['file'].':'.$error['line']);
            if (!headers_sent()) {
                require __DIR__.'/_error.php';
            }
        }
    });
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
require PORTAL_ROOT.'/portal/_db.php';
require PORTAL_ROOT.'/portal/_validate.php';
require PORTAL_ROOT.'/portal/_documents.php';

// Reuse Phase 2's stateless port of EvaluationChecklist/IerApplicationFormatter
// directly — every eval_*() function operates on plain PDO row arrays with
// no session coupling, so it's safe to share across portals (each request
// only ever boots one portal's chain, so there's no redeclare risk).
require PORTAL_ROOT.'/evaluator-portal/_evaluation.php';

// Own copies of the session-coupled helpers, for full independence.
require __DIR__.'/_csrf.php';
require __DIR__.'/_flash.php';
require __DIR__.'/_upload.php';
require __DIR__.'/_mailer.php';

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

    // Re-check role and approval on every request, not just at login time, so
    // revoking an admin's approval takes effect immediately. Admin accounts
    // need approval like evaluators; the main admin account is always allowed.
    if ($user && ($user->role !== 'admin' || (!(int) $user->is_approved && !admin_is_main_account($user)))) {
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
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? admin_url('dashboard.php'));
        header('Location: '.admin_url('login.php').'?redirect='.$redirect);
        exit;
    }
}

function admin_url(string $path): string
{
    return BASE_PATH.'/admin-portal/'.ltrim($path, '/');
}

function asset_url(string $path): string
{
    return BASE_PATH.'/'.ltrim($path, '/');
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
 * Sequential JP-0001 style number, assigned the first time a position is
 * posted and again whenever it is posted as a new posting (new memo).
 * Originally ported from JobPosition::generateJpNumber().
 */
function admin_generate_jp_number(): string
{
    $pdo = portal_pdo();

    // A position posted again under a new JP number gives up its old one, but
    // applications keep it (applications.jp_number) — so continue after the
    // highest number ever used anywhere, never reissuing an old one.
    $next = 1 + (int) $pdo->query(
        "SELECT MAX(n) FROM (
            SELECT CAST(SUBSTRING(jp_number, 4) AS UNSIGNED) AS n FROM job_positions WHERE jp_number LIKE 'JP-%'
            UNION ALL
            SELECT CAST(SUBSTRING(jp_number, 4) AS UNSIGNED) FROM applications WHERE jp_number LIKE 'JP-%'
        ) used"
    )->fetchColumn();

    do {
        $candidate = 'JP-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        $next++;

        $stmt = $pdo->prepare(
            'SELECT 1 FROM job_positions WHERE jp_number = ? UNION ALL SELECT 1 FROM applications WHERE jp_number = ? LIMIT 1'
        );
        $stmt->execute([$candidate, $candidate]);
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

/**
 * Who evaluates a posting's applications (job_positions.evaluated_by).
 * 'admin' postings are evaluated here and hidden from evaluator accounts.
 */
function admin_evaluated_by_options(): array
{
    return [
        'evaluator' => 'Evaluator',
        'admin' => 'Admin',
    ];
}

/** The submitted evaluated_by value, or $fallback when missing/invalid. */
function admin_evaluated_by_input(mixed $value, string $fallback = 'evaluator'): string
{
    return array_key_exists((string) $value, admin_evaluated_by_options()) ? (string) $value : $fallback;
}

/**
 * Every evaluator account, for the "let specific evaluator(s) help" picker
 * shown under the Admin option. Kept separate from admin_evaluated_by_options()
 * since this lists accounts, not the evaluated_by modes themselves.
 *
 * @return array<int, object>
 */
function admin_active_evaluators(): array
{
    return portal_pdo()->query(
        "SELECT id, name, email FROM users WHERE role = 'evaluator' ORDER BY name"
    )->fetchAll();
}

/**
 * Filters submitted evaluator ids down to real evaluator-role accounts only
 * — never trust the client to say who's an evaluator (an id could belong to
 * an admin, or not exist at all).
 *
 * @param array<int, mixed> $ids
 * @return array<int, int>
 */
function admin_valid_evaluator_ids(array $ids): array
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (empty($ids)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = portal_pdo()->prepare(
        "SELECT id FROM users WHERE role = 'evaluator' AND id IN ($placeholders)"
    );
    $stmt->execute($ids);

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/** @return array<int, int> */
function admin_get_assigned_evaluator_ids(int $jobPositionId): array
{
    $stmt = portal_pdo()->prepare(
        'SELECT evaluator_user_id FROM job_position_assigned_evaluators WHERE job_position_id = ?'
    );
    $stmt->execute([$jobPositionId]);

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/**
 * Replaces a posting's helper-evaluator list (evaluators allowed to see and
 * evaluate an Admin-mode posting alongside admins) with exactly
 * $evaluatorIds. Call this after every create/edit/publish save, regardless
 * of evaluated_by — an empty array just clears any existing helpers, which
 * is correct for 'evaluator' mode (the field isn't shown there) too.
 *
 * @param array<int, int> $evaluatorIds
 */
function admin_sync_assigned_evaluators(int $jobPositionId, array $evaluatorIds): void
{
    $pdo = portal_pdo();

    $pdo->prepare('DELETE FROM job_position_assigned_evaluators WHERE job_position_id = ?')->execute([$jobPositionId]);

    if (empty($evaluatorIds)) {
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO job_position_assigned_evaluators (job_position_id, evaluator_user_id, created_at) VALUES (?, ?, NOW())'
    );
    foreach ($evaluatorIds as $evaluatorId) {
        $stmt->execute([$jobPositionId, $evaluatorId]);
    }
}

/**
 * The main administrator account (MAIN_ADMIN_EMAIL in .env, default
 * admin@deped.gov.ph) — the only account shown who evaluated each
 * application and who made its final decision.
 */
function admin_is_main_account(?object $user): bool
{
    $mainEmail = (string) admin_env('MAIN_ADMIN_EMAIL', 'admin@deped.gov.ph');

    return $user !== null && strcasecmp(trim((string) $user->email), trim($mainEmail)) === 0;
}

/**
 * Like admin_require_login(), but only the main admin account may continue —
 * for user management (Users and the user create/edit/approve/delete pages).
 * Other admins are sent back to the dashboard.
 */
function admin_require_main_account(): void
{
    admin_require_login();

    if (!admin_is_main_account(admin_user())) {
        flash_set('error', 'Only the main administrator account can manage users.');
        header('Location: '.admin_url('dashboard.php'));
        exit;
    }
}

/**
 * Show/hide-password eye button for the password input with id $inputId —
 * the same control as on Create User. Place it right after the input inside a
 * `relative` wrapper (give the input `pr-11`); toggleAuthPassword() lives in
 * _layout_footer.php.
 */
function admin_password_toggle(string $inputId): string
{
    $id = admin_e($inputId);

    return '<button type="button" class="absolute right-0 top-0 flex h-full w-10 items-center justify-center rounded-r-lg border-l border-slate-300 bg-slate-50 text-slate-500 hover:text-slate-700" onclick="toggleAuthPassword(\''.$id.'\', this)" aria-label="Show password">'
        .'<svg class="icon-eye h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>'
        .'<svg class="icon-eye-slash hidden h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>'
        .'</button>';
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
 * Groups application rows for the collapsible tables on
 * applications.php/approvals.php/archive.php — one table per posting round
 * of each position (see eval_group_by_posting_round()).
 *
 * @param array<int, object> $applications
 * @return array<string, array<int, object>>
 */
function admin_group_by_position(array $applications): array
{
    return eval_group_by_posting_round($applications);
}

/**
 * Splits admin_group_by_position() tables by who evaluates each posting
 * (rows need ->evaluated_by): admin-evaluated first, then evaluator-
 * evaluated. Empty sections are left out.
 *
 * @param array<string, array<int, object>> $positionGroups
 * @return array<string, array<string, array<int, object>>> keyed 'admin' / 'evaluator'
 */
function admin_split_groups_by_evaluator(array $positionGroups): array
{
    $sections = ['admin' => [], 'evaluator' => []];

    foreach ($positionGroups as $key => $rows) {
        $sections[($rows[0]->evaluated_by ?? 'evaluator') === 'admin' ? 'admin' : 'evaluator'][$key] = $rows;
    }

    return array_filter($sections);
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

/**
 * Heroicons outline paths (24x24, stroke-width 1.5), keyed by name — used by
 * admin_section_header() for the colored section-header bars.
 */
function admin_section_icon(string $name): string
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
function admin_section_header(string $title, string $icon, ?string $subtitle = null, string $actionsHtml = ''): string
{
    $sub = $subtitle ? '<p class="mt-0.5 text-xs text-white/70">'.admin_e($subtitle).'</p>' : '';
    $actions = $actionsHtml ? '<div class="ml-auto flex-shrink-0">'.$actionsHtml.'</div>' : '';

    return '<div class="flex items-center gap-3 bg-admin-indigoDark px-6 py-4">'
        .'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6 flex-shrink-0 text-white"><path stroke-linecap="round" stroke-linejoin="round" d="'.admin_section_icon($icon).'" /></svg>'
        .'<div><h3 class="text-lg font-black text-white">'.admin_e($title).'</h3>'.$sub.'</div>'
        .$actions
        .'</div>';
}
