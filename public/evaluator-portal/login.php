<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

const MAX_ATTEMPTS = 5;
const LOCKOUT_SECONDS = 60;

if (evaluator_user() !== null) {
    header('Location: '.evaluator_url('applications.php'));
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

                $redirect = $_GET['redirect'] ?? evaluator_url('applications.php');
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
require __DIR__.'/_layout_head.php';
?>

<div class="flex flex-1 items-center justify-center px-4 py-12 sm:px-6">
    <div class="w-full max-w-md">
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-evaluator-indigoLight">
                <img src="/images/depedalbay.png" class="h-10 w-10 object-contain" alt="Logo">
            </div>
            <h2 class="text-3xl font-black text-evaluator-indigoDark">Evaluation Desk</h2>
            <p class="mt-2 text-slate-500">Sign in to review applicant qualifications.</p>
        </div>

        <?php if (flash_get('status')): ?>
            <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"><?= evaluator_e(flash_get('status')) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= evaluator_url('login.php') ?><?= isset($_GET['redirect']) ? '?redirect='.urlencode($_GET['redirect']) : '' ?>" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <?= evaluator_csrf_field() ?>

            <div>
                <label class="mb-1 block text-sm font-bold text-slate-700">Email Address</label>
                <input type="email" name="email" value="<?= evaluator_e(evaluator_old('email')) ?>" required autofocus
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-evaluator-indigo focus:ring-2 focus:ring-evaluator-indigo/20">
            </div>

            <div>
                <label class="mb-1 block text-sm font-bold text-slate-700">Password</label>
                <input type="password" name="password" required
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-evaluator-indigo focus:ring-2 focus:ring-evaluator-indigo/20">
            </div>

            <?php if ($errors->has('email')): ?>
                <div id="login-alert-box" class="flex items-start gap-2 rounded-xl border px-4 py-3 text-sm <?= $loginWarning ? 'border-amber-300 bg-amber-50 text-amber-700' : 'border-red-200 bg-red-50 text-red-600' ?>">
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

            <button type="submit" id="login-submit-btn" class="w-full rounded-xl bg-evaluator-indigo py-3.5 text-lg font-bold text-white transition hover:bg-evaluator-indigoDark">
                Login
            </button>
        </form>

        <p class="mt-5 text-center text-xs text-slate-500">Evaluator accounts are created by an Administrator. Contact your Admin if you don't have credentials yet.</p>
    </div>
</div>

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

<?php require __DIR__.'/_layout_footer.php'; ?>
