<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

admin_require_login();
$pdo = portal_pdo();

const PASSWORD_REGEX = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/';

$errors = new Errors();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirmation = (string) ($_POST['passwordConfirmation'] ?? '');
    $role = $_POST['role'] ?? '';
    $isApproved = isset($_POST['is_approved']) && $_POST['is_approved'] === '1';

    if (!v_present($name)) {
        $errors->add('name', 'The name field is required.');
    }
    if (!v_present($email) || !v_email($email)) {
        $errors->add('email', 'The email field must be a valid email address.');
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors->add('email', 'The email has already been taken.');
        }
    }
    if (!v_present($password) || !preg_match(PASSWORD_REGEX, $password)) {
        $errors->add('password', 'At least 8 characters, with Uppercase, lowercase, number, and symbol.');
    }
    if ($passwordConfirmation !== $password) {
        $errors->add('passwordConfirmation', 'The password confirmation does not match.');
    }
    if (!in_array($role, ['admin', 'evaluator'], true)) {
        $errors->add('role', 'Please select a role.');
    }

    if (!$errors->any()) {
        $pdo->prepare(
            'INSERT INTO users (name, email, password, role, is_approved, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())'
        )->execute([$name, $email, password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]), $role, $isApproved ? 1 : 0]);

        flash_set('success', 'User created.');

        if (($_POST['submit_action'] ?? '') === 'create_another') {
            header('Location: '.admin_url('user-create.php'));
            exit;
        }

        header('Location: '.admin_url('users.php'));
        exit;
    }

    flash_set('_errors', $errors->all());
    set_old_input(['name' => $name, 'email' => $email, 'role' => $role, 'is_approved' => $isApproved ? '1' : '']);
    header('Location: '.admin_url('user-create.php'));
    exit;
}

foreach (validation_errors() as $field => $message) {
    $errors->add($field, $message);
}

$pageTitle = 'New User | Administration';
require __DIR__.'/_layout_head.php';
?>

<?php $activePage = 'users'; require __DIR__.'/_layout_topbar.php'; ?>

<div class="lg:flex lg:flex-1">
    <?php require __DIR__.'/_layout_sidebar.php'; ?>

    <div class="min-w-0 flex-1">
        <main class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
            <a href="<?= admin_url('users.php') ?>" class="mb-4 inline-flex items-center gap-1.5 text-sm font-bold text-slate-500 hover:text-admin-indigo">&larr; Back to Users</a>

            <h2 class="mb-6 text-2xl font-black text-admin-indigoDark">New User</h2>

            <?php if (flash_get('success')): ?>
                <div class="mb-4 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800"><?= admin_e(flash_get('success')) ?></div>
            <?php endif; ?>

            <form method="POST" action="<?= admin_url('user-create.php') ?>" class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:grid-cols-2">
                <?= admin_csrf_field() ?>

                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-bold text-slate-700">Name</label>
                    <input type="text" name="name" value="<?= admin_e(admin_old('name')) ?>" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <?php if ($errors->has('name')): ?><p class="mt-1 text-xs text-red-600"><?= admin_e($errors->first('name')) ?></p><?php endif; ?>
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-bold text-slate-700">Email</label>
                    <input type="email" name="email" value="<?= admin_e(admin_old('email')) ?>" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <?php if ($errors->has('email')): ?><p class="mt-1 text-xs text-red-600"><?= admin_e($errors->first('email')) ?></p><?php endif; ?>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-bold text-slate-700">Password</label>
                    <div class="relative">
                        <input id="password" type="password" name="password" required class="w-full rounded-lg border border-slate-300 px-3 py-2 pr-11 text-sm">
                        <button type="button" class="absolute right-0 top-0 flex h-full w-10 items-center justify-center rounded-r-lg border-l border-slate-300 bg-slate-50 text-slate-500 hover:text-slate-700" onclick="toggleAuthPassword('password', this)" aria-label="Show password">
                            <svg class="icon-eye h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <svg class="icon-eye-slash hidden h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                    <?php if ($errors->has('password')): ?><p class="mt-1 text-xs text-red-600"><?= admin_e($errors->first('password')) ?></p><?php endif; ?>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-bold text-slate-700">Confirm Password</label>
                    <div class="relative">
                        <input id="passwordConfirmation" type="password" name="passwordConfirmation" required class="w-full rounded-lg border border-slate-300 px-3 py-2 pr-11 text-sm">
                        <button type="button" class="absolute right-0 top-0 flex h-full w-10 items-center justify-center rounded-r-lg border-l border-slate-300 bg-slate-50 text-slate-500 hover:text-slate-700" onclick="toggleAuthPassword('passwordConfirmation', this)" aria-label="Show password">
                            <svg class="icon-eye h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <svg class="icon-eye-slash hidden h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                    <?php if ($errors->has('passwordConfirmation')): ?><p class="mt-1 text-xs text-red-600"><?= admin_e($errors->first('passwordConfirmation')) ?></p><?php endif; ?>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-bold text-slate-700">Role</label>
                    <select id="role-select" name="role" required onchange="toggleApprovedField()" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Select role</option>
                        <option value="admin" <?= admin_old('role') === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="evaluator" <?= admin_old('role') === 'evaluator' ? 'selected' : '' ?>>Evaluator</option>
                    </select>
                    <?php if ($errors->has('role')): ?><p class="mt-1 text-xs text-red-600"><?= admin_e($errors->first('role')) ?></p><?php endif; ?>
                </div>

                <div id="approved-wrap" class="hidden flex-col justify-center">
                    <label class="inline-flex cursor-pointer items-center gap-3">
                        <span class="relative inline-flex h-6 w-11 shrink-0 items-center">
                            <input type="checkbox" name="is_approved" value="1" class="peer sr-only" <?= admin_old('is_approved') === '1' ? 'checked' : '' ?>>
                            <span class="absolute inset-0 rounded-full bg-slate-300 transition-colors peer-checked:bg-admin-indigo"></span>
                            <span class="absolute left-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
                        </span>
                        <span class="text-sm font-bold text-slate-700">Approved</span>
                    </label>
                    <p class="mt-1.5 text-xs text-slate-500">Evaluator accounts must be approved before they can sign in.</p>
                </div>

                <div class="sm:col-span-2 flex flex-wrap gap-3">
                    <button type="submit" name="submit_action" value="create" class="rounded-lg bg-admin-indigo px-5 py-2.5 text-sm font-bold text-white hover:bg-admin-indigoDark">Create User</button>
                    <button type="submit" name="submit_action" value="create_another" class="rounded-lg border-2 border-admin-indigo px-5 py-2.5 text-sm font-bold text-admin-indigo hover:bg-admin-indigoLight">Create &amp; Create Another</button>
                </div>
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

    function toggleApprovedField() {
        var role = document.getElementById('role-select').value;
        var wrap = document.getElementById('approved-wrap');
        var checkbox = wrap.querySelector('input[name="is_approved"]');
        var show = role === 'evaluator';
        wrap.classList.toggle('hidden', !show);
        wrap.classList.toggle('flex', show);
        if (!show) checkbox.checked = false;
    }

    document.addEventListener('DOMContentLoaded', toggleApprovedField);
</script>

<?php require __DIR__.'/_layout_footer.php'; ?>
