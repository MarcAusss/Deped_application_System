<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

admin_require_login();

$statusFilter = $_GET['status'] ?? '';
$jobFilter = $_GET['job'] ?? '';

$where = ["a.status NOT IN ('disqualified','qualified')"];
$params = [];

if (in_array($statusFilter, ['pending', 'evaluated', 'excluded'], true)) {
    $where[] = 'a.status = :status';
    $params['status'] = $statusFilter;
}
if ($jobFilter !== '' && ctype_digit((string) $jobFilter)) {
    $where[] = 'a.job_position_id = :job';
    $params['job'] = $jobFilter;
}

$sql = 'SELECT a.id, a.status, a.created_at,
               jp.title AS job_title,
               cn.control_number,
               p.full_name, p.email
        FROM applications a
        LEFT JOIN job_positions jp ON jp.id = a.job_position_id
        LEFT JOIN application_control_numbers cn ON cn.application_id = a.id
        LEFT JOIN applicant_profiles p ON p.application_id = a.id
        WHERE '.implode(' AND ', $where).'
        ORDER BY a.created_at DESC';

$stmt = portal_pdo()->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();

$jobs = portal_pdo()->query('SELECT id, title FROM job_positions ORDER BY title')->fetchAll();

$statusBadge = [
    'pending' => 'bg-slate-100 text-slate-600',
    'evaluated' => 'bg-amber-100 text-amber-700',
    'excluded' => 'bg-rose-100 text-rose-700',
];

$pageTitle = 'Applications | Administration';
require __DIR__.'/_layout_head.php';
?>

<?php $activePage = 'applications'; require __DIR__.'/_layout_topbar.php'; ?>

<div class="lg:flex lg:flex-1">
    <?php require __DIR__.'/_layout_sidebar.php'; ?>

    <div class="min-w-0 flex-1">
        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

            <?php if (flash_get('success')): ?>
                <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800"><?= admin_e(flash_get('success')) ?></div>
            <?php endif; ?>
            <?php if (flash_get('error')): ?>
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800"><?= admin_e(flash_get('error')) ?></div>
            <?php endif; ?>

            <div class="mb-6 border-b border-slate-200 pb-6">
                <p class="text-sm font-bold uppercase tracking-widest text-admin-indigo">Administration</p>
                <h2 class="mt-2 text-3xl font-black text-admin-indigoDark">Applications</h2>
                <p class="mt-2 text-slate-600">Review evaluator decisions and finalize hiring outcomes.</p>
            </div>

            <form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Filter by Status</label>
                    <select name="status" onchange="this.form.submit()" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All</option>
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="evaluated" <?= $statusFilter === 'evaluated' ? 'selected' : '' ?>>Evaluated</option>
                        <option value="excluded" <?= $statusFilter === 'excluded' ? 'selected' : '' ?>>Excluded</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Filter by Position</label>
                    <select name="job" onchange="this.form.submit()" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All Positions</option>
                        <?php foreach ($jobs as $job): ?>
                            <option value="<?= $job->id ?>" <?= (string) $jobFilter === (string) $job->id ? 'selected' : '' ?>><?= admin_e($job->title) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-bold text-slate-600">Control #</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-600">Applicant Name</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-600">Email</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-600">Position Applied</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-600">Status</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-600">Applied On</th>
                            <th class="px-4 py-3 text-center font-bold text-slate-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($applications)): ?>
                            <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">No applications found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($applications as $app): $canDecide = in_array($app->status, ['evaluated', 'excluded'], true); ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-mono text-xs"><?= admin_e($app->control_number ?: 'Not assigned') ?></td>
                                <td class="px-4 py-3 font-bold text-slate-800"><?= admin_e($app->full_name ?? '—') ?></td>
                                <td class="px-4 py-3 text-slate-600"><?= admin_e($app->email ?? '—') ?></td>
                                <td class="px-4 py-3 text-slate-600"><?= admin_e($app->job_title ?? '—') ?></td>
                                <td class="px-4 py-3"><span class="pill <?= $statusBadge[$app->status] ?? 'bg-slate-100 text-slate-600' ?>"><?= admin_e(ucfirst($app->status)) ?></span></td>
                                <td class="px-4 py-3 text-slate-500"><?= admin_e(date('M d, Y', strtotime($app->created_at))) ?></td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="<?= admin_url('application-view.php?id='.$app->id) ?>" class="font-bold text-slate-600 hover:text-admin-indigo">View</a>

                                        <form method="POST" action="<?= admin_url('application-status.php') ?>" onsubmit="return confirm('Are you sure you want to mark this application Qualified? This finalizes the hiring decision.');">
                                            <?= admin_csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $app->id ?>">
                                            <input type="hidden" name="action" value="qualify">
                                            <button type="submit" <?= $canDecide ? '' : 'disabled' ?> class="font-bold <?= $canDecide ? 'text-emerald-600 hover:underline' : 'cursor-not-allowed text-slate-300' ?>">Qualified</button>
                                        </form>

                                        <form method="POST" action="<?= admin_url('application-status.php') ?>" onsubmit="return confirm('Are you sure you want to mark this application Disqualified? This application will be moved to archive.');">
                                            <?= admin_csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $app->id ?>">
                                            <input type="hidden" name="action" value="disqualify">
                                            <button type="submit" <?= $canDecide ? '' : 'disabled' ?> class="font-bold <?= $canDecide ? 'text-rose-600 hover:underline' : 'cursor-not-allowed text-slate-300' ?>">Disqualified</button>
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
