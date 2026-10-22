<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

const MAX_ATTEMPTS = 5;
const LOCKOUT_SECONDS = 60;

if (evaluator_user() !== null) {
    header('Location: '.evaluator_url('dashboard.php'));
    exit;
}

function eval_throttle_row(string $key): ?object
{
    $stmt = portal_pdo()->prepare('SELECT * FROM evaluator_login_throttle WHERE throttle_key = ?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function eval_throttle_upsert(string $key, array $data): void
{
    $data['throttle_key'] = $key;
    $data['updated_at'] = date('Y-m-d H:i:s');

    $columns = array_keys($data);
    $placeholders = array_map(fn ($c) => ':'.$c, $columns);
    $updates = array_map(fn ($c) => "$c = VALUES($c)", $columns);

    $sql = sprintf(
        'INSERT INTO evaluator_login_throttle (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s',
        implode(', ', $columns),
        implode(', ', $placeholders),
        implode(', ', $updates)
    );

    portal_pdo()->prepare($sql)->execute($data);
}

function eval_throttle_clear(string $key): void
{
    portal_pdo()->prepare('DELETE FROM evaluator_login_throttle WHERE throttle_key = ?')->execute([$key]);
}

$errors = new Errors();
$lockoutSeconds = null;
$loginWarning = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

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
        $throttle = eval_throttle_row($throttleKey);
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
            $isApprovedEvaluator = $credentialsValid && $user->role === 'evaluator' && (int) $user->is_approved === 1;

            if (!$credentialsValid) {
                $level = (int) ($throttle?->lockout_level ?? 0);
                $levelExpires = $throttle && $throttle->lockout_level_expires_at ? new DateTime($throttle->lockout_level_expires_at) : null;
                $levelStillActive = $level > 0 && $levelExpires !== null && $levelExpires > $now;

                if ($levelStillActive) {
                    $newLevel = $level + 1;
                    $seconds = LOCKOUT_SECONDS * $newLevel;
                    $until = (clone $now)->modify("+{$seconds} seconds");

                    eval_throttle_upsert($throttleKey, [
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

                        eval_throttle_upsert($throttleKey, [
                            'attempts' => 0,
                            'locked_until' => $until->format('Y-m-d H:i:s'),
                            'lockout_level' => 1,
                            'lockout_level_expires_at' => (clone $now)->modify('+1 day')->format('Y-m-d H:i:s'),
                        ]);

                        $lockoutSeconds = LOCKOUT_SECONDS;
                        $errors->add('email', "Too many failed login attempts. Please try again in {$lockoutSeconds} second(s).");
                    } else {
                        eval_throttle_upsert($throttleKey, [
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
            } elseif (!$isApprovedEvaluator) {
                $errors->add('email', 'This account is not an approved Evaluator account.');
            } else {
                eval_throttle_clear($throttleKey);
                evaluator_login((int) $user->id);

                $redirect = $_GET['redirect'] ?? evaluator_url('dashboard.php');
                header('Location: '.$redirect);
                exit;
            }
        }
    }

    flash_set('_errors', $errors->all());
    flash_set('lockoutSeconds', $lockoutSeconds);
    flash_set('loginWarning', $loginWarning);
    set_old_input(['email' => $email]);
    header('Location: '.evaluator_url('login.php').(isset($_GET['redirect']) ? '?redirect='.urlencode($_GET['redirect']) : ''));
    exit;
}

$errors = new Errors();
foreach (validation_errors() as $field => $message) {
    $errors->add($field, $message);
}
$lockoutSeconds = flash_get('lockoutSeconds');
$loginWarning = flash_get('loginWarning', false);

$pageTitle = 'Evaluator Login | DepEd Recruitment Portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= evaluator_e($pageTitle) ?></title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        @media (min-width: 1024px) {
            html { font-size: clamp(10px, min(100vw / 120, 100vh / 60), 16px); }
        }

        .login-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.85);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08), 0 25px 60px rgba(0, 0, 0, 0.10);
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
            border-color: #123B6D;
            box-shadow: 0 0 0 4px rgba(18, 59, 109, 0.12);
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
            background: #123B6D;
            transition: .2s ease;
        }

        .auth-btn:hover {
            background: #1D4E89;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-5 bg-slate-50">

    <div class="login-card w-full max-w-7xl rounded-[2.2rem] overflow-hidden grid grid-cols-1 lg:grid-cols-[3fr_2fr]">

        <div class="relative hidden lg:block min-h-[43.75rem]">
            <img src="<?= asset_url('images/SDO-ALBAY.webp') ?>" class="absolute inset-0 w-full h-full object-cover object-[42%_center]" alt="Background Image">
            <div class="absolute inset-0 bg-white/70"></div>

            <div class="relative z-10 flex flex-col items-center justify-start h-full px-10 pt-60 text-center">
                <div class="w-32 h-32 rounded-full bg-white shadow-2xl flex items-center justify-center border border-white">
                    <img src="<?= asset_url('images/depedalbay.png') ?>" alt="Logo" class="w-30 h-30 object-contain">
                </div>

                <h1 class="text-4xl font-black text-gray-800 mt-8">
                    Career Application &amp; Recruitment for Education Services (CARES)
                </h1>

                <p class="text-gray-700 mt-4 text-lg max-w-md leading-relaxed">
                    Welcome to the online recruitment and application management system.
                </p>
            </div>
        </div>

        <div class="bg-white px-8 md:px-16 py-14 flex items-center">
            <div class="w-full">

                <div class="lg:hidden flex justify-center mb-8">
                    <div class="w-24 h-24 rounded-full bg-indigo-100 flex items-center justify-center shadow-lg">
                        <img src="<?= asset_url('images/depedalbay.png') ?>" class="w-14 h-14 object-contain" alt="Logo">
                    </div>
                </div>

                <div class="mb-10">
                    <h2 class="text-4xl font-black text-[#123B6D]">Evaluation Desk</h2>
                    <p class="mt-3 text-lg text-[#1D4E89]">Sign in to review applicant qualifications.</p>
                </div>

                <?php if (flash_get('status')): ?>
                    <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"><?= evaluator_e(flash_get('status')) ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= evaluator_url('login.php') ?><?= isset($_GET['redirect']) ? '?redirect='.urlencode($_GET['redirect']) : '' ?>" class="space-y-6">
                    <?= evaluator_csrf_field() ?>

                    <div class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="auth-icon h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                        <input type="email" name="email" value="<?= evaluator_e(evaluator_old('email')) ?>" placeholder="Email Address" required autofocus class="auth-input">
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
                                    <?= evaluator_e($errors->first('email')) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <button type="submit" id="login-submit-btn" class="auth-btn w-full text-white py-4 rounded-2xl font-bold text-lg">
                        Login
                    </button>
                </form>

                <p class="mt-6 text-center text-xs text-slate-500">Evaluator accounts are created by an Administrator. Contact your Admin if you don't have credentials yet.</p>

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
                var originalLabel = submitBtn ? submitBtn.textContent.trim() : null;

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
                            submitBtn.textContent = originalLabel;
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
