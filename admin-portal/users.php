<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

admin_require_main_account();
$pdo = portal_pdo();

$admins = $pdo->query("SELECT id, name, email, role, is_approved, created_at FROM users WHERE role = 'admin' ORDER BY created_at DESC")->fetchAll();
$evaluators = $pdo->query("SELECT id, name, email, role, is_approved, created_at FROM users WHERE role = 'evaluator' ORDER BY created_at DESC")->fetchAll();

$pageTitle = 'Users | Administration';
require __DIR__.'/_layout_head.php';
?>

<?php $activePage = 'users'; require __DIR__.'/_layout_topbar.php'; ?>

<div class="lg:flex lg:flex-1">
    <?php require __DIR__.'/_layout_sidebar.php'; ?>

    <div class="min-w-0 flex-1">
        <main class="px-4 py-8 sm:px-6 lg:px-10">

            <?php if (flash_get('success')): ?>
                <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800"><?= admin_e(flash_get('success')) ?></div>
            <?php endif; ?>
            <?php if (flash_get('error')): ?>
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800"><?= admin_e(flash_get('error')) ?></div>
            <?php endif; ?>

            <div class="mb-6 flex flex-wrap items-end justify-between gap-3 border-b border-slate-200 pb-6">
                <div>
                    <p class="text-sm font-bold uppercase tracking-widest text-admin-indigo">Administration</p>
                    <h2 class="mt-2 text-3xl font-black text-admin-indigoDark">Users</h2>
                    <p class="mt-2 text-slate-600">Manage Administrator and Evaluator accounts.</p>
                </div>
                <a href="<?= admin_url('user-create.php') ?>" class="rounded-lg bg-admin-indigo px-4 py-2 text-sm font-bold text-white hover:bg-admin-indigoDark">+ New User</a>
            </div>

            <div class="mb-8">
                <div class="mb-3 flex items-center gap-2">
                    <h3 class="text-lg font-black text-admin-indigoDark">Administrators</h3>
                    <span class="pill bg-rose-100 text-rose-700"><?= count($admins) ?></span>
                </div>
                <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Name</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Email</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Approved</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Created</th>
                                <th class="px-4 py-3 text-center font-bold text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($admins)): ?>
                                <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">No administrator accounts.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($admins as $u): $isMain = admin_is_main_account($u); ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-bold text-slate-800"><?= admin_e($u->name) ?></td>
                                    <td class="px-4 py-3 text-slate-600"><?= admin_e($u->email) ?></td>
                                    <td class="px-4 py-3">
                                        <?php if ($isMain): ?>
                                            <span class="pill bg-admin-indigoLight text-admin-indigo">Admin Account</span>
                                        <?php else: ?>
                                            <?= eval_bool($u->is_approved) ? '<span class="pill bg-emerald-100 text-emerald-700">Approved</span>' : '<span class="pill bg-amber-100 text-amber-700">Pending</span>' ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-slate-500"><?= admin_e(date('M d, Y', strtotime($u->created_at))) ?></td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-3">
                                            <button type="button" class="user-edit-trigger font-bold text-admin-indigo hover:underline" data-id="<?= $u->id ?>">Edit</button>

                                            <?php if (!$isMain && !eval_bool($u->is_approved)): ?>
                                                <form method="POST" action="<?= admin_url('user-approve.php') ?>" class="inline">
                                                    <?= admin_csrf_field() ?>
                                                    <input type="hidden" name="id" value="<?= $u->id ?>">
                                                    <button type="submit" class="font-bold text-emerald-600 hover:underline">Approve</button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if (!$isMain): ?>
                                                <form method="POST" action="<?= admin_url('user-delete.php') ?>" class="inline" data-confirm="Delete this user?">
                                                    <?= admin_csrf_field() ?>
                                                    <input type="hidden" name="id" value="<?= $u->id ?>">
                                                    <button type="submit" class="font-bold text-rose-600 hover:underline">Delete</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <div class="mb-3 flex items-center gap-2">
                    <h3 class="text-lg font-black text-admin-indigoDark">Evaluators</h3>
                    <span class="pill bg-sky-100 text-sky-700"><?= count($evaluators) ?></span>
                </div>
                <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Name</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Email</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Approved</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Created</th>
                                <th class="px-4 py-3 text-center font-bold text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($evaluators)): ?>
                                <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">No evaluator accounts.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($evaluators as $u): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-bold text-slate-800"><?= admin_e($u->name) ?></td>
                                    <td class="px-4 py-3 text-slate-600"><?= admin_e($u->email) ?></td>
                                    <td class="px-4 py-3">
                                        <?= eval_bool($u->is_approved) ? '<span class="pill bg-emerald-100 text-emerald-700">Approved</span>' : '<span class="pill bg-amber-100 text-amber-700">Pending</span>' ?>
                                    </td>
                                    <td class="px-4 py-3 text-slate-500"><?= admin_e(date('M d, Y', strtotime($u->created_at))) ?></td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-3">
                                            <button type="button" class="user-edit-trigger font-bold text-admin-indigo hover:underline" data-id="<?= $u->id ?>">Edit</button>

                                            <?php if (!eval_bool($u->is_approved)): ?>
                                                <form method="POST" action="<?= admin_url('user-approve.php') ?>" class="inline">
                                                    <?= admin_csrf_field() ?>
                                                    <input type="hidden" name="id" value="<?= $u->id ?>">
                                                    <button type="submit" class="font-bold text-emerald-600 hover:underline">Approve</button>
                                                </form>
                                            <?php endif; ?>

                                            <form method="POST" action="<?= admin_url('user-delete.php') ?>" class="inline" data-confirm="Delete this user?">
                                                <?= admin_csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= $u->id ?>">
                                                <button type="submit" class="font-bold text-rose-600 hover:underline">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<div id="user-edit-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4 sm:p-6">
    <div class="max-h-[92vh] w-full max-w-xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-8 py-5">
            <h3 class="text-xl font-black text-admin-indigoDark">Edit User</h3>
            <button type="button" id="user-edit-close" class="text-slate-400 transition hover:text-slate-700" aria-label="Close">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-7 w-7">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form method="POST" action="<?= admin_url('user-edit.php') ?>" id="user-edit-form" class="grid gap-4 px-8 py-6 sm:grid-cols-2">
            <?= admin_csrf_field() ?>
            <input type="hidden" name="id" id="user-edit-field-id" value="">
            <input type="hidden" name="ajax" value="1">

            <div id="user-edit-errors" class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 sm:col-span-2"></div>

            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-bold text-slate-700">Name</label>
                <input type="text" name="name" id="user-edit-field-name" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-bold text-slate-700">Email</label>
                <input type="email" name="email" id="user-edit-field-email" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="mb-1 block text-sm font-bold text-slate-700">New Password</label>
                <div class="relative">
                    <input type="password" id="user-edit-password" name="password" required class="w-full rounded-lg border border-slate-300 px-3 py-2 pr-11 text-sm">
                    <?= admin_password_toggle('user-edit-password') ?>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-bold text-slate-700">Confirm New Password</label>
                <div class="relative">
                    <input type="password" id="user-edit-passwordConfirmation" name="passwordConfirmation" required class="w-full rounded-lg border border-slate-300 px-3 py-2 pr-11 text-sm">
                    <?= admin_password_toggle('user-edit-passwordConfirmation') ?>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-bold text-slate-700">Role</label>
                <select name="role" id="user-edit-field-role" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="admin">Admin</option>
                    <option value="evaluator">Evaluator</option>
                </select>
            </div>

            <div id="user-edit-approved-wrap" class="flex flex-col justify-center">
                <label class="inline-flex cursor-pointer items-center gap-3">
                    <span class="relative inline-flex h-6 w-11 shrink-0 items-center">
                        <input type="checkbox" name="is_approved" id="user-edit-field-is_approved" value="1" class="peer sr-only">
                        <span class="absolute inset-0 rounded-full bg-slate-300 transition-colors peer-checked:bg-admin-indigo"></span>
                        <span class="absolute left-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
                    </span>
                    <span class="text-sm font-bold text-slate-700">Approved</span>
                </label>
                <p class="mt-1.5 text-xs text-slate-500">Admin and evaluator accounts must be approved before they can sign in.</p>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4 sm:col-span-2">
                <button type="button" id="user-edit-cancel" class="rounded-lg px-5 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                <button type="submit" id="user-edit-submit" class="rounded-lg bg-admin-indigo px-5 py-2.5 text-sm font-bold text-white transition hover:bg-admin-indigoDark">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    var USER_EDIT_DATA = <?= json_encode(array_map(fn ($u) => [
        'id' => $u->id,
        'name' => $u->name,
        'email' => $u->email,
        'role' => $u->role,
        'is_approved' => (bool) eval_bool($u->is_approved),
        'is_main' => admin_is_main_account($u),
    ], array_merge($admins, $evaluators))) ?>;

    (function () {
        var modal = document.getElementById('user-edit-modal');
        var closeBtn = document.getElementById('user-edit-close');
        var cancelBtn = document.getElementById('user-edit-cancel');
        var form = document.getElementById('user-edit-form');
        var submitBtn = document.getElementById('user-edit-submit');
        var errorBox = document.getElementById('user-edit-errors');
        if (!modal) return;

        function openModal(user) {
            document.getElementById('user-edit-field-id').value = user.id;
            document.getElementById('user-edit-field-name').value = user.name;
            document.getElementById('user-edit-field-email').value = user.email;
            document.getElementById('user-edit-field-role').value = user.role;
            document.getElementById('user-edit-field-is_approved').checked = user.is_approved;
            // admin@deped.gov.ph is always approved (enforced in user-edit.php), so no switch for it.
            document.getElementById('user-edit-approved-wrap').classList.toggle('hidden', user.is_main);
            form.querySelector('input[name="password"]').value = '';
            form.querySelector('input[name="passwordConfirmation"]').value = '';
            errorBox.classList.add('hidden');
            errorBox.innerHTML = '';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.querySelectorAll('.user-edit-trigger').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var user = USER_EDIT_DATA.find(function (u) { return String(u.id) === btn.dataset.id; });
                if (user) openModal(user);
            });
        });

        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal(); });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            errorBox.classList.add('hidden');
            errorBox.innerHTML = '';
            submitBtn.disabled = true;

            fetch(form.action, { method: 'POST', body: new FormData(form) })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        window.location.href = data.redirect;
                        return;
                    }
                    submitBtn.disabled = false;
                    var messages = Object.keys(data.errors || {}).map(function (field) { return data.errors[field]; });
                    errorBox.innerHTML = messages.map(function (m) { return '<p>' + m + '</p>'; }).join('');
                    errorBox.classList.remove('hidden');
                    errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                })
                .catch(function () {
                    submitBtn.disabled = false;
                    errorBox.textContent = 'Something went wrong. Please try again.';
                    errorBox.classList.remove('hidden');
                });
        });
    })();
</script>

<?php require __DIR__.'/_layout_footer.php'; ?>
