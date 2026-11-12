<?php

/**
 * Unified "Office System" login — a single entry point for both Admin and
 * Evaluator accounts. Mirrors the live site's original shared login design
 * (one form, role-detected after auth) instead of forcing the visitor to
 * already know which portal their account belongs to.
 *
 * Does not belong to either admin-portal/ or evaluator-portal/'s own session
 * — it only decides which of those two sessions to start, once the
 * submitted credentials are verified and the account's role is known, then
 * hands off to that portal's existing dashboard.
 */

define('PORTAL_ROOT', __DIR__);

require PORTAL_ROOT.'/vendor/autoload.php';

if (!isset($_ENV['APP_KEY']) && !getenv('APP_KEY')) {
    $dotenv = Dotenv\Dotenv::createImmutable(PORTAL_ROOT);
    $dotenv->safeLoad();
}

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

$portalDebug = portal_env('APP_DEBUG', false) === true;
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('error_log', PORTAL_ROOT.'/storage/logs/laravel.log');
ini_set('display_errors', $portalDebug ? '1' : '0');

date_default_timezone_set('Asia/Manila');

define('PORTAL_BOOTED', true);
require PORTAL_ROOT.'/_base_path.php';
define('BASE_PATH', app_base_path(0));

function asset_url(string $path): string
{
    return BASE_PATH.'/'.ltrim($path, '/');
}

require PORTAL_ROOT.'/portal/_db.php';
require PORTAL_ROOT.'/portal/_validate.php';

// Own sessions folder + extended lifetime, matching admin-portal/_boot.php
// and evaluator-portal/_boot.php exactly. session_save_path() applies to
// every session_start() for the rest of this request, including the portal
// session staff_start_portal_session() switches to below — without this,
// that session gets written to PHP's default save location while the
// target dashboard (via its own _boot.php) looks for it here, making a
// successful login appear logged-out the moment it redirects.
$sessionDir = PORTAL_ROOT.'/storage/framework/sessions';
if (is_dir($sessionDir) || @mkdir($sessionDir, 0700, true)) {
    session_save_path($sessionDir);
}
ini_set('session.gc_maxlifetime', (string) (max(1, (int) portal_env('SESSION_LIFETIME', 180)) * 60));
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor', '100');

// A session scoped to this page only — holds the CSRF token, throttle
// state, and flashed errors during the login attempt itself. Once a role is
// confirmed below, this is closed in favor of that portal's own session.
session_name('depedcares_stafflogin_sess');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => (($_SERVER['HTTPS'] ?? '') !== '') || (($_SERVER['SERVER_PORT'] ?? '') === '443'),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

function staff_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function staff_e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function staff_start_portal_session(string $sessionName): void
{
    session_write_close();
    session_name($sessionName);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (($_SERVER['HTTPS'] ?? '') !== '') || (($_SERVER['SERVER_PORT'] ?? '') === '443'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
    session_regenerate_id(true);
}

const MAX_ATTEMPTS = 5;
const LOCKOUT_SECONDS = 60;

function staff_throttle_row(string $key): ?object
{
    $stmt = portal_pdo()->prepare('SELECT * FROM admin_login_throttle WHERE throttle_key = ?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function staff_throttle_upsert(string $key, array $data): void
{
    $data['throttle_key'] = $key;
    $data['updated_at'] = date('Y-m-d H:i:s');

    $columns = array_keys($data);
    $placeholders = array_map(fn ($c) => ':'.$c, $columns);
    $updates = array_map(fn ($c) => "$c = VALUES($c)", $columns);

    $sql = sprintf(
        'INSERT INTO admin_login_throttle (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s',
        implode(', ', $columns),
        implode(', ', $placeholders),
        implode(', ', $updates)
    );

    portal_pdo()->prepare($sql)->execute($data);
}

function staff_throttle_clear(string $key): void
{
    portal_pdo()->prepare('DELETE FROM admin_login_throttle WHERE throttle_key = ?')->execute([$key]);
}

$errors = new Errors();
$lockoutSeconds = null;
$loginWarning = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['_csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $submittedToken)) {
        http_response_code(403);
        echo 'Your session has expired. Please go back and try again.';
        exit;
    }

    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (!v_present($email) || !v_email($email)) {
        $errors->add('email', 'The email field must be a valid email address.');
    }
    if (!v_present($password)) {
        $errors->add('password', 'The password field is required.');
    }

    if (!$errors->any()) {
        $throttleKey = strtolower($email).'|'.($_SERVER['REMOTE_ADDR'] ?? '');
        $throttle = staff_throttle_row($throttleKey);
        $now = new DateTime();

        $lockedUntil = $throttle && $throttle->locked_until ? new DateTime($throttle->locked_until) : null;

        if ($lockedUntil !== null && $lockedUntil > $now) {
            $lockoutSeconds = $lockedUntil->getTimestamp() - $now->getTimestamp();
            $errors->add('email', "Too many failed login attempts. Please try again in {$lockoutSeconds} second(s).");
        } else {
            $stmt = portal_pdo()->prepare('SELECT id, name, password, role, is_approved FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            $credentialsValid = $user && password_verify($password, $user->password);
            $role = $credentialsValid ? $user->role : null;
            // Admin and evaluator accounts both need the main admin's approval.
            $isStaffRole = in_array($role, ['admin', 'evaluator'], true);
            $isAwaitingApproval = $isStaffRole && (int) $user->is_approved !== 1;
            $isRecognizedStaff = $credentialsValid && $isStaffRole && !$isAwaitingApproval;

            if (!$credentialsValid) {
                $level = (int) ($throttle?->lockout_level ?? 0);
                $levelExpires = $throttle && $throttle->lockout_level_expires_at ? new DateTime($throttle->lockout_level_expires_at) : null;
                $levelStillActive = $level > 0 && $levelExpires !== null && $levelExpires > $now;

                if ($levelStillActive) {
                    $newLevel = $level + 1;
                    $seconds = LOCKOUT_SECONDS * $newLevel;
                    $until = (clone $now)->modify("+{$seconds} seconds");

                    staff_throttle_upsert($throttleKey, [
                        'attempts' => 0,
                        'locked_until' => $until->format('Y-m-d H:i:s'),
                        'lockout_level' => $newLevel,
                        'lockout_level_expires_at' => (clone $now)->modify('+1 day')->format('Y-m-d H:i:s'),
                    ]);

                    $lockoutSeconds = $seconds;
                    $errors->add('email', "Invalid email or password. Your account has been locked again for {$seconds} second(s).");
                } else {
                    $attempts = (int) ($throttle?->attempts ?? 0) + 1;

                    if ($attempts >= MAX_ATTEMPTS) {
                        $until = (clone $now)->modify('+'.LOCKOUT_SECONDS.' seconds');

                        staff_throttle_upsert($throttleKey, [
                            'attempts' => 0,
                            'locked_until' => $until->format('Y-m-d H:i:s'),
                            'lockout_level' => 1,
                            'lockout_level_expires_at' => (clone $now)->modify('+1 day')->format('Y-m-d H:i:s'),
                        ]);

                        $lockoutSeconds = LOCKOUT_SECONDS;
                        $errors->add('email', "Too many failed login attempts. Please try again in {$lockoutSeconds} second(s).");
                    } else {
                        staff_throttle_upsert($throttleKey, [
                            'attempts' => $attempts,
                            'locked_until' => null,
                            'lockout_level' => $level,
                            'lockout_level_expires_at' => $throttle?->lockout_level_expires_at ?? null,
                        ]);

                        $remaining = MAX_ATTEMPTS - $attempts;
                        $loginWarning = $remaining <= 2;
                        $errors->add('email', "Invalid email or password. {$remaining} attempt(s) remaining before your account is temporarily locked.");
                    }
                }
            } elseif ($isAwaitingApproval) {
                $errors->add('email', 'This account has not been approved yet. Please wait for the main administrator to approve it.');
            } elseif (!$isRecognizedStaff) {
                $errors->add('email', 'This account is not an active Administrator or approved Evaluator account.');
            } else {
                staff_throttle_clear($throttleKey);

                if ($role === 'admin') {
                    staff_start_portal_session('depedcares_admin_sess');
                    $_SESSION['admin_user_id'] = $user->id;
                    $redirect = $_GET['redirect'] ?? asset_url('admin-portal/dashboard.php');
                } else {
                    staff_start_portal_session('depedcares_evaluator_sess');
                    $_SESSION['evaluator_user_id'] = $user->id;
                    $redirect = $_GET['redirect'] ?? asset_url('evaluator-portal/dashboard.php');
                }

                header('Location: '.$redirect);
                exit;
            }
        }
    }

    $_SESSION['_old_input'] = ['email' => $email];
    $_SESSION['_errors'] = $errors->all();
    $_SESSION['_lockout_seconds'] = $lockoutSeconds;
    $_SESSION['_login_warning'] = $loginWarning;
    header('Location: '.asset_url('staff-login.php').(isset($_GET['redirect']) ? '?redirect='.urlencode($_GET['redirect']) : ''));
    exit;
}

$oldInput = $_SESSION['_old_input'] ?? [];
$flashedErrors = $_SESSION['_errors'] ?? [];
$lockoutSeconds = $_SESSION['_lockout_seconds'] ?? null;
$loginWarning = $_SESSION['_login_warning'] ?? false;
unset($_SESSION['_old_input'], $_SESSION['_errors'], $_SESSION['_lockout_seconds'], $_SESSION['_login_warning']);

foreach ($flashedErrors as $field => $message) {
    $errors->add($field, $message);
}
$oldEmail = $oldInput['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Login | SDO Albay CARES</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        @media (min-width: 1024px) {
            html { font-size: clamp(10px, min(100vw / 120, 100vh / 60), 16px); }
        }

        @keyframes loginCardIn {
            from { opacity: 0; transform: translateY(18px) scale(.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .login-card {
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.35), 0 45px 110px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(212, 160, 23, 0.08);
            animation: loginCardIn .6s cubic-bezier(.16, 1, .3, 1);
        }

        .page-bg-photo {
            position: fixed;
            inset: 0;
            background-image: url('<?= asset_url('images/mayon-dawn.jpg') ?>');
            background-size: cover;
            background-position: center 60%;
            background-repeat: no-repeat;
            filter: blur(3px) saturate(1.15) brightness(1.02);
            transform: scale(1.05);
        }

        .page-bg-tint {
            position: fixed;
            inset: 0;
            background: rgba(45, 74, 66, 0.32);
        }

        .cares-logo-glow {
            position: absolute;
            inset: 0;
            margin: auto;
            width: 12rem;
            height: 12rem;
            border-radius: 9999px;
            background: radial-gradient(circle, rgba(212, 160, 23, 0.35) 0%, rgba(212, 160, 23, 0) 70%);
            filter: blur(6px);
        }

        .auth-input {
            width: 100%;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            border-radius: 12px;
            padding: .85rem 1rem .85rem 2.75rem;
            outline: none;
            transition: .2s ease;
            color: #0f172a;
        }

        .auth-input:focus {
            border-color: #1E6B42;
            box-shadow: 0 0 0 4px rgba(30, 107, 66, 0.12);
        }

        .auth-icon {
            position: absolute;
            left: .9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .auth-toggle {
            position: absolute;
            right: .9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            cursor: pointer;
        }

        .auth-btn {
            background: #1E6B42;
            transition: .2s ease;
            box-shadow: 0 8px 20px rgba(30, 107, 66, 0.35);
        }

        .auth-btn:hover {
            background: #278253;
            transform: translateY(-2px);
            box-shadow: 0 12px 26px rgba(30, 107, 66, 0.45);
        }

        .auth-btn:active {
            transform: translateY(0);
        }
    </style>
    <style>
        /* Edge adds its own eye button to password fields; these pages have their own show-password toggle. */
        input[type="password"]::-ms-reveal, input[type="password"]::-ms-clear { display: none; }
    </style>
</head>

<body class="relative min-h-screen flex items-center justify-center p-5 bg-[#1F5A3D] overflow-hidden">

    <div class="page-bg-photo"></div>
    <div class="page-bg-tint"></div>

    <div class="login-card relative z-10 w-full max-w-7xl rounded-[2.2rem] overflow-hidden grid grid-cols-1 lg:grid-cols-[3fr_2fr]">

        <div class="relative hidden lg:flex flex-col items-center justify-center min-h-[43.75rem] px-10 py-10 text-center">
            <img src="<?= asset_url('images/mayon-dawn.jpg') ?>" class="absolute inset-0 w-full h-full object-cover object-[center_60%]" alt="Mayon Volcano at dawn">
            <div class="absolute inset-0 bg-[#1F5A3D]/32"></div>

            <div class="absolute left-6 top-6 z-20 flex items-center gap-2">
                <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-full bg-white shadow-md ring-2 ring-white/20">
                    <img src="<?= asset_url('images/depedalbay.png') ?>" alt="DepEd SDO Albay" class="h-16 w-16 shrink-0 translate-y-0.5 object-contain">
                </div>
                <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-full bg-white shadow-md ring-2 ring-white/20">
                    <img src="<?= asset_url('images/Department_of_Education_(DepEd).svg.webp') ?>" alt="Department of Education" class="h-10 w-10 object-contain">
                </div>
            </div>

            <div class="relative z-10 flex flex-col items-center">
                <div class="relative mt-32 flex items-center justify-center">
                    <div class="cares-logo-glow"></div>
                    <img src="<?= asset_url('images/cares-logo.svg') ?>" alt="SDO Albay CARES logo" class="relative h-36 w-36 drop-shadow-xl">
                </div>

                <h1 class="text-4xl font-black text-white mt-8">
                    Career Application &amp; Recruitment for Education Services (CARES)
                </h1>

                <div class="mt-3 h-1 w-16 rounded-full bg-[#D4A017]"></div>

                <p class="text-white/85 mt-4 text-lg max-w-md leading-relaxed">
                    Welcome to the online recruitment and application management system.
                </p>
            </div>
        </div>

        <div class="bg-white px-8 md:px-16 py-14 flex items-center">
            <div class="w-full">

                <div class="lg:hidden flex items-center justify-center gap-3 mb-6">
                    <div class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-full bg-white shadow ring-1 ring-slate-200">
                        <img src="<?= asset_url('images/depedalbay.png') ?>" alt="DepEd SDO Albay" class="h-14 w-14 shrink-0 translate-y-0.5 object-contain">
                    </div>
                    <img src="<?= asset_url('images/cares-logo.svg') ?>" alt="SDO Albay CARES logo" class="h-24 w-24 drop-shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-full bg-white shadow ring-1 ring-slate-200">
                        <img src="<?= asset_url('images/Department_of_Education_(DepEd).svg.webp') ?>" alt="Department of Education" class="h-10 w-10 object-contain">
                    </div>
                </div>

                <div class="mb-8">
                    <h2 class="text-3xl font-black text-slate-900">Welcome to CARES!</h2>
                    <p class="mt-2 text-base text-slate-600">Sign in to access the Office System — Administrator and Evaluator accounts both work here.</p>
                </div>

                <form method="POST" action="<?= asset_url('staff-login.php') ?><?= isset($_GET['redirect']) ? '?redirect='.urlencode($_GET['redirect']) : '' ?>" class="space-y-6">
                    <input type="hidden" name="_csrf_token" value="<?= staff_e(staff_csrf_token()) ?>">

                    <div class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="auth-icon h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                        <input type="email" name="email" value="<?= staff_e($oldEmail) ?>" placeholder="Email Address" required autofocus class="auth-input">
                    </div>

                    <div class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="auth-icon h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                        <input id="password" type="password" name="password" placeholder="Password" required class="auth-input">
                        <button type="button" class="auth-toggle" onclick="toggleAuthPassword('password', this)" aria-label="Show password">
                            <svg class="icon-eye h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <svg class="icon-eye-slash hidden h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>

                    <?php if ($errors->has('email')): ?>
                        <div id="login-alert-box" class="flex items-start gap-2 rounded-2xl border px-4 py-3 text-sm <?= $loginWarning ? 'border-amber-300 bg-amber-50 text-amber-700' : 'border-red-200 bg-red-50 text-red-600' ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mt-0.5 h-5 w-5 shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-8.25 3.75h.008v.008h-.008v-.008Z" />
                            </svg>
                            <span id="login-error-text">
                                <?php if ($lockoutSeconds): ?>
                                    Too many failed login attempts. Please try again in <span id="lockout-countdown"><?= (int) $lockoutSeconds ?></span> second(s).
                                <?php else: ?>
                                    <?= staff_e($errors->first('email')) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <button type="submit" id="login-submit-btn" class="auth-btn w-full flex items-center justify-center gap-2 text-white py-4 rounded-2xl font-bold text-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                        </svg>
                        <span id="login-submit-label">Login</span>
                    </button>
                </form>

            </div>
        </div>

        <script>
            function toggleAuthPassword(inputId, button) {
                const input = document.getElementById(inputId);
                const willShow = input.type === 'password';
                input.type = willShow ? 'text' : 'password';
                button.querySelector('.icon-eye').classList.toggle('hidden', willShow);
                button.querySelector('.icon-eye-slash').classList.toggle('hidden', !willShow);
                button.setAttribute('aria-label', willShow ? 'Hide password' : 'Show password');
            }
        </script>

        <?php if ($lockoutSeconds): ?>
        <script>
            (function () {
                var seconds = <?= (int) $lockoutSeconds ?>;
                var countdownEl = document.getElementById('lockout-countdown');
                var textEl = document.getElementById('login-error-text');
                var alertBox = document.getElementById('login-alert-box');
                var submitBtn = document.getElementById('login-submit-btn');
                var submitLabel = document.getElementById('login-submit-label');
                var originalLabel = submitLabel ? submitLabel.textContent.trim() : null;

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                }

                var interval = setInterval(function () {
                    seconds--;
                    if (seconds <= 0) {
                        clearInterval(interval);
                        if (textEl) textEl.textContent = 'You can try logging in again now.';
                        if (alertBox) {
                            alertBox.classList.remove('border-red-200', 'bg-red-50', 'text-red-600', 'border-amber-300', 'bg-amber-50', 'text-amber-700');
                            alertBox.classList.add('border-green-300', 'bg-green-50', 'text-green-700');
                        }
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                            if (submitLabel) submitLabel.textContent = originalLabel;
                        }
                        return;
                    }
                    if (countdownEl) countdownEl.textContent = seconds;
                }, 1000);
            })();
        </script>
        <?php endif; ?>

    </div>

</body>
</html>
