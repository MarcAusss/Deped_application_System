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
                    <input type="password" name="password" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <?php if ($errors->has('password')): ?><p class="mt-1 text-xs text-red-600"><?= admin_e($errors->first('password')) ?></p><?php endif; ?>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-bold text-slate-700">Confirm Password</label>
                    <input type="password" name="passwordConfirmation" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <?php if ($errors->has('passwordConfirmation')): ?><p class="mt-1 text-xs text-red-600"><?= admin_e($errors->first('passwordConfirmation')) ?></p><?php endif; ?>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-bold text-slate-700">Role</label>
                    <select name="role" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Select role</option>
                        <option value="admin" <?= admin_old('role') === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="evaluator" <?= admin_old('role') === 'evaluator' ? 'selected' : '' ?>>Evaluator</option>
                    </select>
                    <?php if ($errors->has('role')): ?><p class="mt-1 text-xs text-red-600"><?= admin_e($errors->first('role')) ?></p><?php endif; ?>
                </div>

                <div class="flex items-end">
                    <label class="flex items-center gap-2 text-sm font-bold text-slate-700">
                        <input type="checkbox" name="is_approved" value="1" <?= admin_old('is_approved') === '1' ? 'checked' : '' ?>>
                        Approved (required for Evaluator panel login)
                    </label>
                </div>

                <div class="sm:col-span-2">
                    <button type="submit" class="rounded-lg bg-admin-indigo px-5 py-2.5 text-sm font-bold text-white hover:bg-admin-indigoDark">Create User</button>
                </div>
            </form>
        </main>
    </div>
</div>

<?php require __DIR__.'/_layout_footer.php'; ?>
