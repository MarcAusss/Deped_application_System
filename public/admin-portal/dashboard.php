<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

admin_require_login();

$pdo = portal_pdo();

$pendingCount = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'")->fetchColumn();
// "For Final Action" = awaiting an admin Qualify/Disqualify decision —
// matches $canDecide elsewhere (applications.php etc.), which enables those
// actions for both 'evaluated' and 'excluded', not just 'evaluated'.
$approvalCount = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status IN ('evaluated', 'excluded')")->fetchColumn();
// Matches jobs.php's own WHERE clause — a stale is_open=1 row whose deadline
// has already passed must not count as "open" here either.
$openPositionCount = (int) $pdo->query(
    "SELECT COUNT(*) FROM job_positions
     WHERE is_open = 1
       AND (until IS NULL OR TIMESTAMP(until, COALESCE(until_time, '23:59:59')) >= NOW())"
)->fetchColumn();

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
     ORDER BY total DESC'
)->fetchAll();
$perJobTotal = array_sum(array_map(fn ($j) => (int) $j->total, $perJob));
$perJobLabels = array_map(
    fn ($j) => $j->title.' ('.($perJobTotal > 0 ? round(((int) $j->total / $perJobTotal) * 100) : 0).'%)',
    $perJob
);

$recentSort = ($_GET['sort'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$recent = $pdo->query(
    "SELECT a.id, a.status, a.created_at, jp.title AS job_title, cn.control_number, p.full_name, p.email
     FROM applications a
     LEFT JOIN job_positions jp ON jp.id = a.job_position_id
     LEFT JOIN application_control_numbers cn ON cn.application_id = a.id
     LEFT JOIN applicant_profiles p ON p.application_id = a.id
     ORDER BY a.created_at {$recentSort}
     LIMIT 6"
)->fetchAll();

$statusBadge = [
    'pending' => 'bg-slate-100 text-slate-600',
    'evaluated' => 'bg-amber-100 text-amber-700',
    'excluded' => 'bg-rose-100 text-rose-700',
    'qualified' => 'bg-emerald-100 text-emerald-700',
    'disqualified' => 'bg-rose-100 text-rose-700',
];

/** Mirrors RecentApplicants widget's formatStateUsing() exactly. */
function admin_recent_status_label(?string $state): string
{
    return match ($state) {
        'evaluated' => 'For Qualification',
        null => 'Unknown',
        default => ucwords(str_replace('_', ' ', (string) $state)),
    };
}

$admin = admin_user();
$firstName = explode(' ', trim($admin->name ?? 'Administrator'))[0];
$hour = (int) date('G');
$greeting = match (true) {
    $hour < 12 => 'Good morning',
    $hour < 18 => 'Good afternoon',
    default => 'Good evening',
};

$pageTitle = 'Dashboard | Administration';
require __DIR__.'/_layout_head.php';
?>
<style>
    .cmd-hero {
        position: relative;
        display: grid;
        grid-template-columns: minmax(0, 1.65fr) minmax(17rem, 0.72fr);
        overflow: hidden;
        border: 1px solid #312e81;
        border-radius: 1.15rem;
        background:
            radial-gradient(circle at 72% -15%, rgba(45, 212, 191, 0.27), transparent 35%),
            linear-gradient(118deg, #211d55 0%, #3730a3 64%, #0f766e 128%);
        color: #ffffff;
        box-shadow: 0 18px 45px rgba(30, 27, 75, 0.17);
    }
    .cmd-hero::after {
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(255, 255, 255, 0.035) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.035) 1px, transparent 1px);
        background-size: 34px 34px;
        content: '';
        mask-image: linear-gradient(90deg, transparent 34%, #000);
        pointer-events: none;
    }
    .cmd-hero__main, .cmd-hero__brief { position: relative; z-index: 1; }
    .cmd-hero__main { padding: clamp(1.55rem, 3vw, 2.45rem); }
    .cmd-hero__eyebrow {
        display: flex; flex-wrap: wrap; align-items: center; gap: .65rem; margin-bottom: 1.1rem;
        color: #c7d2fe; font-size: .68rem; font-weight: 800; letter-spacing: .13em; text-transform: uppercase;
    }
    .cmd-hero__live {
        display: inline-flex; align-items: center; gap: .42rem;
        border: 1px solid rgba(153, 246, 228, .28); border-radius: 999px;
        background: rgba(15, 118, 110, .24); color: #ccfbf1; padding: .32rem .62rem; letter-spacing: .06em;
    }
    .cmd-hero__live::before {
        width: .42rem; height: .42rem; border-radius: 999px; background: #5eead4;
        box-shadow: 0 0 0 4px rgba(94, 234, 212, .13); content: ''; display: inline-block;
    }
    .cmd-hero__title { max-width: 46rem; margin: 0; color: #fff; font-size: clamp(1.65rem, 3.2vw, 2.55rem); font-weight: 760; letter-spacing: -.035em; line-height: 1.08; }
    .cmd-hero__description { max-width: 44rem; margin: .85rem 0 0; color: #d8dcf8; font-size: .9rem; line-height: 1.65; }
    .cmd-hero__actions { display: flex; flex-wrap: wrap; gap: .7rem; margin-top: 1.45rem; }
    .cmd-hero__action {
        display: inline-flex; min-height: 2.65rem; align-items: center; justify-content: center; gap: .55rem;
        border: 1px solid rgba(255, 255, 255, .25); border-radius: .7rem; padding: .62rem .95rem;
        color: #fff; font-size: .76rem; font-weight: 750;
        transition: transform 150ms ease, background-color 150ms ease, border-color 150ms ease;
    }
    .cmd-hero__action:hover { border-color: rgba(255, 255, 255, .45); background: rgba(255, 255, 255, .1); transform: translateY(-1px); }
    .cmd-hero__action--primary { border-color: #fff; background: #fff; color: #312e81; }
    .cmd-hero__action--primary:hover { border-color: #e0e7ff; background: #e0e7ff; }
    .cmd-hero__action svg { width: 1rem; height: 1rem; }
    .cmd-hero__brief {
        display: flex; flex-direction: column; justify-content: center;
        border-left: 1px solid rgba(255, 255, 255, .13); background: rgba(11, 16, 44, .2);
        padding: clamp(1.35rem, 2.5vw, 2rem);
    }
    .cmd-hero__brief-label { color: #a5b4fc; font-size: .64rem; font-weight: 800; letter-spacing: .13em; text-transform: uppercase; }
    .cmd-hero__brief-date { margin-top: .35rem; color: #fff; font-size: .98rem; font-weight: 700; }
    .cmd-hero__brief-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); margin-top: 1.25rem; border-top: 1px solid rgba(255, 255, 255, .14); }
    .cmd-hero__brief-item { min-width: 0; padding: 1rem .75rem 0 0; }
    .cmd-hero__brief-item + .cmd-hero__brief-item { border-left: 1px solid rgba(255, 255, 255, .13); padding-left: .75rem; }
    .cmd-hero__brief-value { display: block; color: #fff; font-size: 1.45rem; font-weight: 780; letter-spacing: -.035em; }
    .cmd-hero__brief-name { display: block; margin-top: .22rem; color: #c7d2fe; font-size: .62rem; font-weight: 650; line-height: 1.35; }

    @media (max-width: 1024px) {
        .cmd-hero { grid-template-columns: 1fr; }
        .cmd-hero__brief { border-top: 1px solid rgba(255, 255, 255, .13); border-left: 0; }
    }
    @media (max-width: 640px) {
        .cmd-hero { border-radius: .9rem; }
        .cmd-hero__main, .cmd-hero__brief { padding: 1.25rem; }
        .cmd-hero__title { font-size: 1.65rem; }
        .cmd-hero__action { width: 100%; }
        .cmd-hero__brief-grid { grid-template-columns: 1fr; }
        .cmd-hero__brief-item { padding: .85rem 0; }
        .cmd-hero__brief-item + .cmd-hero__brief-item { border-left: 0; border-top: 1px solid rgba(255, 255, 255, .13); padding-left: 0; padding-top: .85rem; margin-top: .85rem; }
    }

    .portfolio-section {
        position: relative; border: 1px solid #e5e7eb; border-radius: 1rem; background: #fff;
        padding: 1.25rem 1.25rem 1.25rem 1.6rem; box-shadow: 0 10px 28px rgba(16, 24, 40, .055);
    }
    .portfolio-section::before {
        position: absolute; left: 1.25rem; top: 1.35rem; bottom: 1.35rem; width: 4px; border-radius: 999px;
        background: linear-gradient(180deg, #4f46e5, #0f766e); content: '';
    }
    .portfolio-section__heading { color: #101828; font-size: 1rem; font-weight: 780; letter-spacing: -.018em; }
    .portfolio-section__description { margin-top: .15rem; color: #7a8496; font-size: .76rem; }

    .stat-card { position: relative; overflow: hidden; border: 1px solid #e5e7eb; border-radius: .85rem; background: #fff; padding: 1.1rem 1.15rem 1rem; transition: border-color 150ms ease, box-shadow 150ms ease, transform 150ms ease; }
    .stat-card::before { position: absolute; inset: 0 0 auto; height: 3px; content: ''; }
    .stat-card:hover { border-color: #cbd5e1; box-shadow: 0 10px 22px rgba(16, 24, 40, .075); transform: translateY(-2px); }
    .stat-card__icon { position: absolute; top: 1rem; right: 1rem; width: 2.45rem; height: 2.45rem; border-radius: .72rem; padding: .58rem; }
    .stat-card__icon svg { width: 100%; height: 100%; }
    .stat-card__label { color: #475467; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; padding-right: 3rem; }
    .stat-card__value { margin-top: .4rem; color: #101828; font-size: 1.75rem; font-weight: 800; letter-spacing: -.02em; }
    .stat-card__desc { margin-top: .25rem; color: #7a8496; font-size: .74rem; }

    .stat-card--indigo { background: linear-gradient(145deg, #fff 58%, #eef2ff 130%); }
    .stat-card--indigo::before { background: #4f46e5; }
    .stat-card--indigo .stat-card__icon { background: #eef2ff; color: #4338ca; }

    .stat-card--teal { background: linear-gradient(145deg, #fff 58%, #ecfdf5 130%); }
    .stat-card--teal::before { background: #0f766e; }
    .stat-card--teal .stat-card__icon { background: #ecfdf5; color: #0f766e; }

    .stat-card--slate { background: linear-gradient(145deg, #fff 58%, #f1f5f9 130%); }
    .stat-card--slate::before { background: #64748b; }
    .stat-card--slate .stat-card__icon { background: #f1f5f9; color: #475569; }

    .stat-card--amber { background: linear-gradient(145deg, #fff 58%, #fffbeb 130%); }
    .stat-card--amber::before { background: #d97706; }
    .stat-card--amber .stat-card__icon { background: #fffbeb; color: #b45309; }

    .stat-card--emerald { background: linear-gradient(145deg, #fff 58%, #ecfdf5 130%); }
    .stat-card--emerald::before { background: #059669; }
    .stat-card--emerald .stat-card__icon { background: #ecfdf5; color: #047857; }

    .stat-card--rose { background: linear-gradient(145deg, #fff 58%, #fff1f2 130%); }
    .stat-card--rose::before { background: #e11d48; }
    .stat-card--rose .stat-card__icon { background: #fff1f2; color: #be123c; }

    .chart-card { display: flex; flex-direction: column; overflow: hidden; border: 1px solid #dfe3ea; border-radius: .9rem; background: #fff; box-shadow: 0 8px 24px rgba(16, 24, 40, .05); }
    .chart-card__header { border-bottom: 1px solid #1e3a8a; background: #1e3a8a; padding: 1.1rem 1.25rem; }
    .chart-card__heading { color: #fff; font-size: 1rem; font-weight: 760; letter-spacing: -.012em; }
    .chart-card__description { margin-top: .15rem; color: #c7d2fe; font-size: .76rem; }
    .chart-card__body { flex: 1; padding: 1.5rem 1.25rem 1.25rem; }
</style>

<?php $activePage = 'dashboard'; require __DIR__.'/_layout_topbar.php'; ?>

<div class="lg:flex lg:flex-1">
    <?php require __DIR__.'/_layout_sidebar.php'; ?>

    <div class="min-w-0 flex-1">
        <main class="px-4 py-8 sm:px-6 lg:px-10">

            <?php if (flash_get('success')): ?>
                <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800"><?= admin_e(flash_get('success')) ?></div>
            <?php endif; ?>

            <section class="cmd-hero mb-6">
                <div class="cmd-hero__main">
                    <div class="cmd-hero__eyebrow">
                        <span>Administration command center</span>
                        <span class="cmd-hero__live">System overview</span>
                    </div>

                    <h1 class="cmd-hero__title"><?= admin_e($greeting) ?>, <?= admin_e($firstName) ?>.</h1>

                    <p class="cmd-hero__description">
                        Monitor the recruitment pipeline, review evaluator decisions, and keep every
                        application moving toward a timely final action.
                    </p>

                    <div class="cmd-hero__actions">
                        <a class="cmd-hero__action cmd-hero__action--primary" href="<?= admin_url('applications.php') ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                            Review applications
                        </a>
                        <a class="cmd-hero__action" href="<?= admin_url('job-positions.php') ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.653v-.443c0-1.09-.542-2.106-1.416-2.72a45.36 45.36 0 0 0-6.984-4.163V4.5A2.25 2.25 0 0 0 12 2.25h0a2.25 2.25 0 0 0-2.25 2.25v1.171a45.36 45.36 0 0 0-6.984 4.163C1.892 10.198 1.35 11.214 1.35 12.304v.443c0 .646.279 1.253.75 1.653" /></svg>
                            Manage job positions
                        </a>
                    </div>
                </div>

                <aside class="cmd-hero__brief">
                    <span class="cmd-hero__brief-label">Operational brief</span>
                    <span class="cmd-hero__brief-date"><?= admin_e(date('l, d F Y')) ?></span>

                    <div class="cmd-hero__brief-grid">
                        <div class="cmd-hero__brief-item">
                            <strong class="cmd-hero__brief-value"><?= number_format($pendingCount) ?></strong>
                            <span class="cmd-hero__brief-name">Awaiting evaluation</span>
                        </div>
                        <div class="cmd-hero__brief-item">
                            <strong class="cmd-hero__brief-value"><?= number_format($approvalCount) ?></strong>
                            <span class="cmd-hero__brief-name">For final action</span>
                        </div>
                        <div class="cmd-hero__brief-item">
                            <strong class="cmd-hero__brief-value"><?= number_format($openPositionCount) ?></strong>
                            <span class="cmd-hero__brief-name">Open positions</span>
                        </div>
                    </div>
                </aside>
            </section>

            <section class="portfolio-section mb-8">
                <h3 class="portfolio-section__heading">Recruitment portfolio</h3>
                <p class="portfolio-section__description">A concise view of workload, decisions, and staffing capacity.</p>

                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
                    <?php
                    $tiles = [
                        ['Evaluators', $evaluatorCount, 'Registered accounts', 'indigo', '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />'],
                        ['Total Applicants', $totalApplicants, 'All submissions', 'teal', '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />'],
                        ['Awaiting Evaluation', $pendingCount, 'In evaluator queue', 'slate', '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />'],
                        ['For Final Action', $approvalCount, 'Evaluation complete', 'amber', '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 4.556-3.04 8.252-7.071 9.606a.75.75 0 0 1-.858 0C9.04 20.252 6 16.556 6 12V5.457a.75.75 0 0 1 .515-.712 48.9 48.9 0 0 1 10.97 0 .75.75 0 0 1 .515.712V12Z" />'],
                        ['Qualified', $qualifiedCount, 'Final decisions', 'emerald', '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />'],
                        ['Disqualified', $disqualifiedCount, 'In Archive', 'rose', '<path stroke-linecap="round" stroke-linejoin="round" d="M15 9l-6 6m0-6 6 6m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />'],
                    ];
                    foreach ($tiles as [$label, $value, $desc, $tone, $iconPath]): ?>
                        <div class="stat-card stat-card--<?= $tone ?>">
                            <span class="stat-card__icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><?= $iconPath ?></svg>
                            </span>
                            <p class="stat-card__label"><?= admin_e($label) ?></p>
                            <p class="stat-card__value"><?= number_format($value) ?></p>
                            <p class="stat-card__desc"><?= admin_e($desc) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="chart-card">
                    <div class="chart-card__header">
                        <p class="chart-card__heading">Recruitment pipeline</p>
                        <p class="chart-card__description">Current volume at every decision stage.</p>
                    </div>
                    <div class="chart-card__body" style="position: relative; height: 300px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <div class="chart-card__header">
                        <p class="chart-card__heading">Applicant demand</p>
                        <p class="chart-card__description">Share of applications received by position.</p>
                    </div>
                    <div class="chart-card__body" style="position: relative; height: 300px;">
                        <canvas id="jobChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-card__header">
                    <p class="chart-card__heading">Recent applicant activity</p>
                    <p class="chart-card__description">Latest submissions across all open and closed positions.</p>
                </div>

                <div class="flex justify-end border-b border-slate-200 px-4 py-3 sm:px-6">
                    <div class="relative w-full max-w-xs">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <input type="search" id="recent-search" placeholder="Search" class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm outline-none focus:border-admin-indigo focus:ring-2 focus:ring-admin-indigo/20">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold text-slate-600 sm:px-6">Control No.</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600 sm:px-6">Applicant</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600 sm:px-6">Position</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600 sm:px-6">Status</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600 sm:px-6">
                                    <a href="<?= admin_url('dashboard.php?sort='.($recentSort === 'DESC' ? 'asc' : 'desc')) ?>" class="inline-flex items-center gap-1 hover:text-admin-indigo">
                                        Date Applied
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5 transition-transform <?= $recentSort === 'ASC' ? 'rotate-180' : '' ?>">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </a>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="recent-tbody" class="divide-y divide-slate-100">
                            <?php if (empty($recent)): ?>
                                <tr>
                                    <td colspan="5" class="px-4 py-14 text-center sm:px-6">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mx-auto h-10 w-10 text-slate-300">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                        </svg>
                                        <p class="mt-3 font-bold text-slate-600">No applications yet</p>
                                        <p class="mt-1 text-sm text-slate-400">Newly submitted applications will appear here.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($recent as $app): ?>
                                <tr class="recent-row view-trigger cursor-pointer hover:bg-slate-50" data-search="<?= admin_e(mb_strtolower($app->control_number.' '.$app->full_name.' '.$app->email.' '.$app->job_title)) ?>" data-id="<?= $app->id ?>">
                                    <td class="px-4 py-3 font-mono text-xs sm:px-6"><?= admin_e($app->control_number ?: 'Not assigned') ?></td>
                                    <td class="px-4 py-3 sm:px-6">
                                        <p class="font-bold text-slate-800"><?= admin_e($app->full_name ?? '—') ?></p>
                                        <?php if ($app->email): ?><p class="text-xs text-slate-500"><?= admin_e($app->email) ?></p><?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600 sm:px-6"><?= admin_e($app->job_title ?? 'No position') ?></td>
                                    <td class="px-4 py-3 sm:px-6"><span class="pill <?= $statusBadge[$app->status] ?? 'bg-slate-100 text-slate-600' ?>"><?= admin_e(admin_recent_status_label($app->status)) ?></span></td>
                                    <td class="px-4 py-3 text-slate-500 sm:px-6"><?= admin_e(date('M d, Y h:i A', strtotime($app->created_at))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<div id="view-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4 sm:p-6">
    <div class="flex h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <h3 class="text-lg font-black text-admin-indigoDark">Application Details</h3>
            <button type="button" id="view-modal-close" class="text-slate-400 transition hover:text-slate-700" aria-label="Close">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <iframe id="view-modal-frame" class="h-full w-full flex-1" src="about:blank"></iframe>
    </div>
</div>

<script>
    (function () {
        var modal = document.getElementById('view-modal');
        var closeBtn = document.getElementById('view-modal-close');
        var frame = document.getElementById('view-modal-frame');
        if (!modal) return;

        function openModal(id) {
            frame.src = '<?= admin_url('application-view.php') ?>?id=' + encodeURIComponent(id) + '&embed=1';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            frame.src = 'about:blank';
        }

        document.querySelectorAll('.view-trigger').forEach(function (el) {
            el.addEventListener('click', function () { openModal(el.dataset.id); });
        });

        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal(); });
    })();
</script>

<script>
    new Chart(document.getElementById('statusChart'), {
        type: 'bar',
        data: {
            labels: ['Pending', 'For Qualification', 'Qualified', 'Disqualified'],
            datasets: [{
                label: 'Applications',
                data: <?= json_encode($statusChartData) ?>,
                backgroundColor: ['#94a3b8', '#4f46e5', '#15803d', '#dc2626'],
                borderColor: ['#64748b', '#3730a3', '#14532d', '#991b1b'],
                borderWidth: 1,
                borderRadius: 7,
                borderSkipped: false,
                barPercentage: 0.9,
                categoryPercentage: 0.95,
            }]
        },
        options: {
            maintainAspectRatio: false,
            layout: { padding: 0 },
            plugins: { legend: { display: false } },
            scales: {
                x: { title: { display: true, text: 'Status' }, grid: { display: false } },
                y: { beginAtZero: true, title: { display: true, text: 'Applications' }, ticks: { precision: 0 }, grid: { color: 'rgba(148, 163, 184, 0.18)' } }
            }
        }
    });

    new Chart(document.getElementById('jobChart'), {
        type: 'pie',
        data: {
            labels: <?= json_encode($perJobLabels) ?>,
            datasets: [{
                label: 'Number of Applicants',
                data: <?= json_encode(array_map(fn ($j) => (int) $j->total, $perJob)) ?>,
                backgroundColor: ['#4f46e5', '#0f766e', '#f59e0b', '#64748b', '#db2777', '#0891b2'],
                borderColor: '#ffffff',
                borderWidth: 3,
                hoverOffset: 5,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: true, position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 16 } }
            }
        }
    });

    (function () {
        var search = document.getElementById('recent-search');
        if (!search) return;
        search.addEventListener('input', function () {
            var term = search.value.trim().toLowerCase();
            document.querySelectorAll('.recent-row').forEach(function (row) {
                row.classList.toggle('hidden', term !== '' && row.dataset.search.indexOf(term) === -1);
            });
        });
    })();
</script>

<?php require __DIR__.'/_layout_footer.php'; ?>
