<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

admin_require_login();
$pdo = portal_pdo();

$roleFilter = $_GET['role'] ?? '';
$where = [];
$params = [];
if (in_array($roleFilter, ['admin', 'evaluator'], true)) {
    $where[] = 'role = :role';
    $params['role'] = $roleFilter;
}

$sql = 'SELECT id, name, email, role, is_approved, created_at FROM users'
     .($where ? ' WHERE '.implode(' AND ', $where) : '')
     .' ORDER BY created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$roleBadge = ['admin' => 'bg-rose-100 text-rose-700', 'evaluator' => 'bg-sky-100 text-sky-700'];

$pageTitle = 'Users | Administration';
require __DIR__.'/_layout_head.php';
?>

<?php $activePage = 'users'; require __DIR__.'/_layout_topbar.php'; ?>

<div class="lg:flex lg:flex-1">
    <?php require __DIR__.'/_layout_sidebar.php'; ?>

    <div class="min-w-0 flex-1">
        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">

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

            <form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Filter by Role</label>
                    <select name="role" onchange="this.form.submit()" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All</option>
                        <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="evaluator" <?= $roleFilter === 'evaluator' ? 'selected' : '' ?>>Evaluator</option>
                    </select>
                </div>
            </form>

            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-bold text-slate-600">Name</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-600">Email</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-600">Role</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-600">Approved</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-600">Created</th>
                            <th class="px-4 py-3 text-center font-bold text-slate-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($users)): ?>
                            <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">No users found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($users as $u): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-bold text-slate-800"><?= admin_e($u->name) ?></td>
                                <td class="px-4 py-3 text-slate-600"><?= admin_e($u->email) ?></td>
                                <td class="px-4 py-3"><span class="pill <?= $roleBadge[$u->role] ?? 'bg-slate-100 text-slate-600' ?>"><?= admin_e(ucfirst($u->role)) ?></span></td>
                                <td class="px-4 py-3">
                                    <?php if ($u->role === 'evaluator'): ?>
                                        <?= eval_bool($u->is_approved) ? '<span class="pill bg-emerald-100 text-emerald-700">Approved</span>' : '<span class="pill bg-amber-100 text-amber-700">Pending</span>' ?>
                                    <?php else: ?>
                                        <span class="text-slate-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-slate-500"><?= admin_e(date('M d, Y', strtotime($u->created_at))) ?></td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-3">
                                        <a href="<?= admin_url('user-edit.php?id='.$u->id) ?>" class="font-bold text-admin-indigo hover:underline">Edit</a>

                                        <?php if ($u->role === 'evaluator' && !eval_bool($u->is_approved)): ?>
                                            <form method="POST" action="<?= admin_url('user-approve.php') ?>" class="inline">
                                                <?= admin_csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= $u->id ?>">
                                                <button type="submit" class="font-bold text-emerald-600 hover:underline">Approve</button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="POST" action="<?= admin_url('user-delete.php') ?>" class="inline" onsubmit="return confirm('Delete this user?');">
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
        </main>
    </div>
</div>

<?php require __DIR__.'/_layout_footer.php'; ?>
