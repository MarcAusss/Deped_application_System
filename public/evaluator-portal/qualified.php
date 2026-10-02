<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

evaluator_require_login();

$jobFilter = $_GET['job'] ?? '';
$where = ["a.status = 'qualified'"];
$params = [];
if ($jobFilter !== '' && ctype_digit((string) $jobFilter)) {
    $where[] = 'a.job_position_id = :job';
    $params['job'] = $jobFilter;
}

$sql = 'SELECT a.id, a.updated_at,
               jp.title AS job_title,
               cn.control_number,
               p.full_name, p.email
        FROM applications a
        LEFT JOIN job_positions jp ON jp.id = a.job_position_id
        LEFT JOIN application_control_numbers cn ON cn.application_id = a.id
        LEFT JOIN applicant_profiles p ON p.application_id = a.id
        WHERE '.implode(' AND ', $where).'
        ORDER BY a.updated_at DESC';

$stmt = portal_pdo()->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();

$jobs = portal_pdo()->query('SELECT id, title FROM job_positions ORDER BY title')->fetchAll();

$pageTitle = 'Qualified | Evaluation Desk';
require __DIR__.'/_layout_head.php';
?>

<?php $activePage = 'qualified'; require __DIR__.'/_layout_topbar.php'; ?>

<div class="lg:flex lg:flex-1">
    <?php require __DIR__.'/_layout_sidebar.php'; ?>

    <div class="min-w-0 flex-1">
        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-6 border-b border-slate-200 pb-6">
                <p class="text-sm font-bold uppercase tracking-widest text-evaluator-indigo">Evaluation Desk</p>
                <h2 class="mt-2 text-3xl font-black text-evaluator-indigoDark">Qualified</h2>
                <p class="mt-2 text-slate-600">Applications finalized as Qualified by the Admin.</p>
            </div>

            <form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Position</label>
                    <select name="job" onchange="this.form.submit()" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All Positions</option>
                        <?php foreach ($jobs as $job): ?>
                            <option value="<?= $job->id ?>" <?= (string) $jobFilter === (string) $job->id ? 'selected' : '' ?>><?= evaluator_e($job->title) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <form method="POST" action="<?= evaluator_url('ier-preview.php') ?>" id="bulk-export-form">
                <?= evaluator_csrf_field() ?>
                <input type="hidden" name="source" value="selected">

                <div class="mb-3 flex items-center justify-between">
                    <span class="text-sm text-slate-500"><?= count($applications) ?> application(s)</span>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-bold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-40" id="export-selected-btn" disabled>
                        Preview &amp; Export Selected IER
                    </button>
                </div>

                <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3"><input type="checkbox" id="select-all"></th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Control #</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Applicant Name</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Email</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Position</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Qualified On</th>
                                <th class="px-4 py-3 text-center font-bold text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($applications)): ?>
                                <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">No qualified applications yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($applications as $app): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3"><input type="checkbox" name="ids[]" value="<?= $app->id ?>" class="row-check"></td>
                                    <td class="px-4 py-3 font-mono text-xs"><?= evaluator_e($app->control_number ?: 'Not assigned') ?></td>
                                    <td class="px-4 py-3 font-bold text-slate-800"><?= evaluator_e($app->full_name ?? '—') ?></td>
                                    <td class="px-4 py-3 text-slate-600"><?= evaluator_e($app->email ?? '—') ?></td>
                                    <td class="px-4 py-3 text-slate-600"><?= evaluator_e($app->job_title ?? '—') ?></td>
                                    <td class="px-4 py-3 text-slate-500"><?= evaluator_e(date('M d, Y', strtotime($app->updated_at))) ?></td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="<?= evaluator_url('view.php?id='.$app->id) ?>" class="font-bold text-evaluator-indigo hover:underline">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </main>
    </div>
</div>

<script>
    var selectAll = document.getElementById('select-all');
    var checks = document.querySelectorAll('.row-check');
    var exportBtn = document.getElementById('export-selected-btn');
    function refreshExportBtn() {
        var any = Array.prototype.some.call(checks, function (c) { return c.checked; });
        exportBtn.disabled = !any;
    }
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checks.forEach(function (c) { c.checked = selectAll.checked; });
            refreshExportBtn();
        });
    }
    checks.forEach(function (c) { c.addEventListener('change', refreshExportBtn); });
</script>

<?php require __DIR__.'/_layout_footer.php'; ?>
