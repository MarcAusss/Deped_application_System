<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

$errors = new Errors();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email = trim($_POST['email'] ?? '');

    if (!v_present($email) || !v_email($email)) {
        $errors->add('email', 'The email field must be a valid email address.');
    }

    if (!$errors->any()) {
        $stmt = portal_pdo()->prepare('SELECT id, name, email FROM applicants WHERE email = ?');
        $stmt->execute([$email]);
        $applicant = $stmt->fetch();

        if (!$applicant) {
            $errors->add('email', 'We could not find an applicant account with that email address.');
        } else {
            $code = (string) random_int(100000, 999999);

            portal_pdo()->prepare(
                'INSERT INTO applicant_password_reset_tokens (email, token, created_at)
                 VALUES (:email, :token, :created_at)
                 ON DUPLICATE KEY UPDATE token = VALUES(token), created_at = VALUES(created_at)'
            )->execute([
                'email' => $applicant->email,
                'token' => password_hash($code, PASSWORD_BCRYPT, ['cost' => 12]),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            send_reset_code($applicant->email, $applicant->name, $code);

            flash_set('status', 'We have emailed a 6-digit verification code to your address.');
            header('Location: '.portal_url('reset-password.php').'?email='.urlencode($applicant->email));
            exit;
        }
    }

    flash_set('_errors', $errors->all());
    set_old_input(['email' => $email]);
    header('Location: '.portal_url('forgot-password.php'));
    exit;
}

$errors = new Errors();
foreach (validation_errors() as $field => $message) {
    $errors->add($field, $message);
}
$prefillEmail = portal_old('email', $_GET['email'] ?? '');

$pageTitle = 'Forgot Password | DepEd Recruitment Portal';
require __DIR__.'/_layout_head.php';
?>

<?php $activePage = 'login'; require __DIR__.'/_layout_topbar.php'; ?>

<div class="lg:flex lg:flex-1">
    <?php require __DIR__.'/_layout_sidebar.php'; ?>

    <div class="min-w-0 flex-1">
        <main class="mx-auto flex max-w-md flex-col justify-center px-4 py-12 sm:px-6">
            <a href="<?= portal_url('login.php') ?>" class="mb-4 inline-flex items-center gap-1.5 text-sm font-bold text-slate-500 hover:text-government-navy">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Back to Login
            </a>

            <div class="mb-8">
                <h2 class="text-3xl font-black text-government-dark">Forgot Password?</h2>
                <p class="mt-2 text-slate-500">Enter your email and we'll email you a 6-digit verification code to reset your password.</p>
            </div>

            <form method="POST" action="<?= portal_url('forgot-password.php') ?>" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <?= portal_csrf_field() ?>

                <div>
                    <label class="mb-1 block text-sm font-bold text-slate-700">Email Address</label>
                    <input type="email" name="email" value="<?= portal_e($prefillEmail) ?>" required autofocus
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20">
                    <?php if ($errors->has('email')): ?><p class="mt-1 text-sm text-red-600"><?= portal_e($errors->first('email')) ?></p><?php endif; ?>
                </div>

                <button type="submit" class="w-full rounded-xl bg-government-navy py-3.5 text-lg font-bold text-white transition hover:bg-government-blue">
                    Send Verification Code
                </button>
            </form>
        </main>
    </div>
</div>

<?php require __DIR__.'/_layout_footer.php'; ?>
