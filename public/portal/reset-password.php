<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

const RESET_CODE_EXPIRY_MINUTES = 60;

$errors = new Errors();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email = trim($_POST['email'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

    if (!v_present($email) || !v_email($email)) {
        $errors->add('email', 'The email field must be a valid email address.');
    }
    if (!v_present($code) || !preg_match('/^\d{6}$/', $code)) {
        $errors->add('code', 'The code field must be exactly 6 digits.');
    }
    if (!v_present($password)) {
        $errors->add('password', 'The password field is required.');
    } elseif (!v_min($password, 8)) {
        $errors->add('password', 'The password field must be at least 8 characters.');
    } elseif ($password !== $passwordConfirmation) {
        $errors->add('password', 'The password field confirmation does not match.');
    }

    if (!$errors->any()) {
        $stmt = portal_pdo()->prepare('SELECT token, created_at FROM applicant_password_reset_tokens WHERE email = ?');
        $stmt->execute([$email]);
        $record = $stmt->fetch();

        if (!$record || !password_verify($code, $record->token)) {
            $errors->add('code', 'That code is invalid. Please check it and try again.');
        } elseif ((new DateTime($record->created_at))->modify('+'.RESET_CODE_EXPIRY_MINUTES.' minutes') < new DateTime()) {
            portal_pdo()->prepare('DELETE FROM applicant_password_reset_tokens WHERE email = ?')->execute([$email]);
            $errors->add('code', 'This code has expired. Please request a new one.');
        } else {
            $stmt = portal_pdo()->prepare('SELECT id FROM applicants WHERE email = ?');
            $stmt->execute([$email]);
            $applicant = $stmt->fetch();

            if (!$applicant) {
                $errors->add('email', 'We could not find an applicant account with that email address.');
            } else {
                portal_pdo()->prepare('UPDATE applicants SET password = ?, updated_at = ? WHERE id = ?')->execute([
                    password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
                    date('Y-m-d H:i:s'),
                    $applicant->id,
                ]);

                portal_pdo()->prepare('DELETE FROM applicant_password_reset_tokens WHERE email = ?')->execute([$email]);

                flash_set('status', 'Your password has been reset. Please login.');
                header('Location: '.portal_url('login.php'));
                exit;
            }
        }
    }

    flash_set('_errors', $errors->all());
    set_old_input(['email' => $email]);
    header('Location: '.portal_url('reset-password.php').'?email='.urlencode($email));
    exit;
}

$errors = new Errors();
foreach (validation_errors() as $field => $message) {
    $errors->add($field, $message);
}
$email = $_GET['email'] ?? '';

$pageTitle = 'Reset Password | DepEd Recruitment Portal';
require __DIR__.'/_layout_head.php';
?>

<?php $activePage = 'login'; require __DIR__.'/_layout_topbar.php'; ?>

<div class="lg:flex lg:flex-1">
    <?php require __DIR__.'/_layout_sidebar.php'; ?>

    <div class="min-w-0 flex-1">
        <main class="mx-auto flex max-w-md flex-col justify-center px-4 py-12 sm:px-6">
            <div class="mb-8">
                <h2 class="text-3xl font-black text-government-dark">Reset Password</h2>
                <p class="mt-2 text-slate-500">Enter the 6-digit code we emailed you, then choose a new password.</p>
            </div>

            <?php if (flash_get('status')): ?>
                <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"><?= portal_e(flash_get('status')) ?></div>
            <?php endif; ?>

            <form method="POST" action="<?= portal_url('reset-password.php') ?>" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <?= portal_csrf_field() ?>

                <div>
                    <label class="mb-1 block text-sm font-bold text-slate-700">Email Address</label>
                    <input type="email" name="email" value="<?= portal_e(portal_old('email', $email)) ?>" required autofocus
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20">
                    <?php if ($errors->has('email')): ?><p class="mt-1 text-sm text-red-600"><?= portal_e($errors->first('email')) ?></p><?php endif; ?>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-bold text-slate-700">6-Digit Verification Code</label>
                    <input type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" name="code" required
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 tracking-[0.3em] outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20">
                    <?php if ($errors->has('code')): ?><p class="mt-1 text-sm text-red-600"><?= portal_e($errors->first('code')) ?></p><?php endif; ?>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-bold text-slate-700">New Password</label>
                    <div class="relative">
                        <input id="password" type="password" name="password" required
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 pr-11 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20">
                        <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" onclick="toggleAuthPassword('password', this)" aria-label="Show password">
                            <svg class="icon-eye h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <svg class="icon-eye-slash hidden h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                    <?php if ($errors->has('password')): ?><p class="mt-1 text-sm text-red-600"><?= portal_e($errors->first('password')) ?></p><?php endif; ?>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-bold text-slate-700">Confirm New Password</label>
                    <div class="relative">
                        <input id="password_confirmation" type="password" name="password_confirmation" required
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 pr-11 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20">
                        <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" onclick="toggleAuthPassword('password_confirmation', this)" aria-label="Show password">
                            <svg class="icon-eye h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <svg class="icon-eye-slash hidden h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="w-full rounded-xl bg-government-navy py-3.5 text-lg font-bold text-white transition hover:bg-government-blue">
                    Reset Password
                </button>
            </form>
        </main>
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

<?php require __DIR__.'/_layout_footer.php'; ?>
