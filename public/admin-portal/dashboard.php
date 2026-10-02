<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

admin_require_login();

$pdo = portal_pdo();

$pendingCount = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'")->fetchColumn();
$approvalCount = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'evaluated'")->fetchColumn();
$openPositionCount = (int) $pdo->query('SELECT COUNT(*) FROM job_positions WHERE is_open = 1')->fetchColumn();

$evaluatorCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'evaluator'")->fetchColumn();
$totalApplicants = (int) $pdo->query('SELECT COUNT(*) FROM applications')->fetchColumn();
$qualifiedCount = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'qualified'")->fetchColumn();
$disqualifiedCount = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'disqualified'")->fetchColumn();

$statusChartData = [$pendingCount, $approvalCount, $qualifiedCount, $disqualifiedCount];

$perJob = $pdo->query(
    'SELECT jp.title, COUNT(a.id) AS total
     FROM job_positions jp
     LEFT JOIN applications a ON a.job_position_id = jp.id
     GROUP BY jp.id, jp.title
     HAVING total > 0
     ORDER BY total DESC'
)->fetchAll();

$recent = $pdo->query(
    'SELECT a.id, a.status, a.created_at, jp.title AS job_title, cn.control_number, p.full_name
     FROM applications a
     LEFT JOIN job_positions jp ON jp.id = a.job_position_id
     LEFT JOIN application_control_numbers cn ON cn.application_id = a.id
     LEFT JOIN applicant_profiles p ON p.application_id = a.id
     ORDER BY a.created_at DESC
     LIMIT 6'
)->fetchAll();

$statusBadge = [
    'pending' => 'bg-slate-100 text-slate-600',
    'evaluated' => 'bg-amber-100 text-amber-700',
    'excluded' => 'bg-rose-100 text-rose-700',
    'qualified' => 'bg-emerald-100 text-emerald-700',
    'disqualified' => 'bg-rose-100 text-rose-700',
];

$pageTitle = 'Dashboard | Administration';
require __DIR__.'/_layout_head.php';
?>

<?php $activePage = 'dashboard'; require __DIR__.'/_layout_topbar.php'; ?>

<div class="lg:flex lg:flex-1">
    <?php require __DIR__.'/_layout_sidebar.php'; ?>

    <div class="min-w-0 flex-1">
        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

            <?php if (flash_get('success')): ?>
                <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800"><?= admin_e(flash_get('success')) ?></div>
            <?php endif; ?>

            <div class="mb-6 border-b border-slate-200 pb-6">
                <p class="text-sm font-bold uppercase tracking-widest text-admin-indigo">Recruitment Overview</p>
                <h2 class="mt-2 text-3xl font-black text-admin-indigoDark">Dashboard</h2>
                <p class="mt-2 text-slate-600">
                    <strong><?= number_format($pendingCount) ?></strong> awaiting evaluation &middot;
                    <strong><?= number_format($approvalCount) ?></strong> awaiting your decision &middot;
                    <strong><?= number_format($openPositionCount) ?></strong> open position(s)
                </p>
            </div>

            <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
                <?php
                $tiles = [
                    ['Evaluators', $evaluatorCount, 'text-admin-indigo'],
                    ['Total Applicants', $totalApplicants, 'text-slate-700'],
                    ['Awaiting Evaluation', $pendingCount, 'text-slate-500'],
                    ['For Final Action', $approvalCount, 'text-amber-600'],
                    ['Qualified', $qualifiedCount, 'text-emerald-600'],
                    ['Disqualified', $disqualifiedCount, 'text-rose-600'],
                ];
                foreach ($tiles as [$label, $value, $color]): ?>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500"><?= admin_e($label) ?></p>
                        <p class="mt-2 text-3xl font-black <?= $color ?>"><?= number_format($value) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-lg font-black text-admin-indigoDark">Applications by Status</h3>
                    <canvas id="statusChart" height="220"></canvas>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-lg font-black text-admin-indigoDark">Applications per Job Position</h3>
                    <canvas id="jobChart" height="220"></canvas>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h3 class="text-lg font-black text-admin-indigoDark">Recent Applicants</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Control #</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Applicant</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Position</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Status</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Applied On</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($recent)): ?>
                                <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">No applications yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($recent as $app): ?>
                                <tr class="cursor-pointer hover:bg-slate-50" onclick="window.location='<?= admin_url('application-view.php?id='.$app->id) ?>'">
                                    <td class="px-4 py-3 font-mono text-xs"><?= admin_e($app->control_number ?: 'Not assigned') ?></td>
                                    <td class="px-4 py-3 font-bold text-slate-800"><?= admin_e($app->full_name ?? '—') ?></td>
                                    <td class="px-4 py-3 text-slate-600"><?= admin_e($app->job_title ?? '—') ?></td>
                                    <td class="px-4 py-3"><span class="pill <?= $statusBadge[$app->status] ?? 'bg-slate-100 text-slate-600' ?>"><?= admin_e(ucfirst($app->status)) ?></span></td>
                                    <td class="px-4 py-3 text-slate-500"><?= admin_e(date('M d, Y', strtotime($app->created_at))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    new Chart(document.getElementById('statusChart'), {
        type: 'bar',
        data: {
            labels: ['Pending', 'For Qualification', 'Qualified', 'Disqualified'],
            datasets: [{
                data: <?= json_encode($statusChartData) ?>,
                backgroundColor: ['#94a3b8', '#f59e0b', '#10b981', '#f43f5e'],
                borderRadius: 6,
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });

    new Chart(document.getElementById('jobChart'), {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_map(fn ($j) => $j->title, $perJob)) ?>,
            datasets: [{
                data: <?= json_encode(array_map(fn ($j) => (int) $j->total, $perJob)) ?>,
                backgroundColor: ['#4338CA', '#0891B2', '#10B981', '#F59E0B', '#F43F5E', '#8B5CF6', '#EC4899', '#84CC16'],
            }]
        },
        options: {
            plugins: { legend: { position: 'bottom' } }
        }
    });
</script>

<?php require __DIR__.'/_layout_footer.php'; ?>
