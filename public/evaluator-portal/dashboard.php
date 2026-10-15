<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

evaluator_require_login();
$user = evaluator_user();

$pdo = portal_pdo();

$pendingCount = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'")->fetchColumn();
$evaluatedCount = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'evaluated'")->fetchColumn();
$excludedCount = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'excluded'")->fetchColumn();
$qualifiedCount = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'qualified'")->fetchColumn();
$disqualifiedCount = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'disqualified'")->fetchColumn();
$totalApplications = (int) $pdo->query('SELECT COUNT(*) FROM applications')->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM application_evaluations WHERE evaluator_id = ? AND evaluated_at IS NOT NULL');
$stmt->execute([$user->id]);
$evaluatedByYouCount = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM application_evaluations WHERE evaluator_id = ? AND recommended = 1');
$stmt->execute([$user->id]);
$recommendedByYouCount = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM application_evaluations WHERE evaluator_id = ? AND DATE(evaluated_at) = CURDATE()');
$stmt->execute([$user->id]);
$reviewedTodayCount = (int) $stmt->fetchColumn();

// Six-month cumulative submission trend — a running total (not a per-month
// count) so the line reflects overall pipeline growth, matching how
// "waiting in queue" style figures are read at a glance.
$monthKeys = [];
for ($i = 5; $i >= 0; $i--) {
    $monthKeys[] = date('Y-m', strtotime("-{$i} months"));
}
$trendLabels = array_map(fn ($m) => date('M Y', strtotime($m.'-01')), $monthKeys);
$trendData = [];
foreach ($monthKeys as $m) {
    $endOfMonth = date('Y-m-t 23:59:59', strtotime($m.'-01'));
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM applications WHERE created_at <= ?');
    $stmt->execute([$endOfMonth]);
    $trendData[] = (int) $stmt->fetchColumn();
}

$perJob = $pdo->query(
    'SELECT jp.title, COUNT(a.id) AS total
     FROM job_positions jp
     LEFT JOIN applications a ON a.job_position_id = jp.id
     GROUP BY jp.id, jp.title
     ORDER BY total DESC
     LIMIT 10'
)->fetchAll();

$queueLabels = ['Pending', 'Evaluated', 'Excluded', 'Qualified', 'Disqualified'];
$queueData = [$pendingCount, $evaluatedCount, $excludedCount, $qualifiedCount, $disqualifiedCount];
$queueColors = ['#94a3b8', '#d97706', '#e11d48', '#15803d', '#dc2626'];

$latestPending = $pdo->query(
    "SELECT a.id, a.status, a.created_at, jp.title AS job_title, cn.control_number, p.full_name, p.email
     FROM applications a
     LEFT JOIN job_positions jp ON jp.id = a.job_position_id
     LEFT JOIN application_control_numbers cn ON cn.application_id = a.id
     LEFT JOIN applicant_profiles p ON p.application_id = a.id
     WHERE a.status = 'pending'
     ORDER BY a.created_at DESC
     LIMIT 8"
)->fetchAll();

$stmt = $pdo->prepare(
    "SELECT a.id, jp.title AS job_title, p.full_name, e.recommended, e.evaluated_at
     FROM application_evaluations e
     JOIN applications a ON a.id = e.application_id
     LEFT JOIN job_positions jp ON jp.id = a.job_position_id
     LEFT JOIN applicant_profiles p ON p.application_id = a.id
     WHERE e.evaluator_id = ? AND e.evaluated_at IS NOT NULL
     ORDER BY e.evaluated_at DESC
     LIMIT 6"
);
$stmt->execute([$user->id]);
$recentEvaluations = $stmt->fetchAll();

$firstName = explode(' ', trim($user->name ?? 'Evaluator'))[0];
$hour = (int) date('G');
$greeting = match (true) {
    $hour < 12 => 'Good morning',
    $hour < 18 => 'Good afternoon',
    default => 'Good evening',
};

$pageTitle = 'Dashboard | Evaluation Desk';
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
        cursor: pointer; background: transparent;
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

    .stat-card--amber { background: linear-gradient(145deg, #fff 58%, #fffbeb 130%); }
    .stat-card--amber::before { background: #d97706; }
    .stat-card--amber .stat-card__icon { background: #fffbeb; color: #b45309; }

    .stat-card--teal { background: linear-gradient(145deg, #fff 58%, #ecfdf5 130%); }
    .stat-card--teal::before { background: #0f766e; }
    .stat-card--teal .stat-card__icon { background: #ecfdf5; color: #0f766e; }

    .stat-card--emerald { background: linear-gradient(145deg, #fff 58%, #ecfdf5 130%); }
    .stat-card--emerald::before { background: #059669; }
    .stat-card--emerald .stat-card__icon { background: #ecfdf5; color: #047857; }

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
                <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800"><?= evaluator_e(flash_get('success')) ?></div>
            <?php endif; ?>

            <section class="cmd-hero mb-6">
                <div class="cmd-hero__main">
                    <div class="cmd-hero__eyebrow">
                        <span>Evaluator workspace</span>
                        <span class="cmd-hero__live">Evaluation queue</span>
                    </div>

                    <h1 class="cmd-hero__title"><?= evaluator_e($greeting) ?>, <?= evaluator_e($firstName) ?>.</h1>

                    <p class="cmd-hero__description">
                        Continue applicant assessment from one focused workspace. Review submitted
                        credentials, record recommendations, and prepare the IER when the queue is ready.
                    </p>

                    <div class="cmd-hero__actions">
                        <a class="cmd-hero__action cmd-hero__action--primary" href="<?= evaluator_url('applications.php') ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.03 0 1.9.693 2.166 1.638m-5.8 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664M6.75 7.5h10.5a2.25 2.25 0 0 1 2.25 2.25v10.5a2.25 2.25 0 0 1-2.25 2.25H6.75a2.25 2.25 0 0 1-2.25-2.25V9.75A2.25 2.25 0 0 1 6.75 7.5Z" /></svg>
                            Open evaluation queue
                        </a>
                        <form method="POST" action="<?= evaluator_url('ier-preview.php') ?>" class="inline-flex">
                            <?= evaluator_csrf_field() ?>
                            <input type="hidden" name="source" value="filtered">
                            <input type="hidden" name="status" value="">
                            <input type="hidden" name="job" value="">
                            <button type="submit" class="cmd-hero__action">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                                View IER export
                            </button>
                        </form>
                    </div>
                </div>

                <aside class="cmd-hero__brief">
                    <span class="cmd-hero__brief-label">Your desk today</span>
                    <span class="cmd-hero__brief-date"><?= evaluator_e(date('l, d F Y')) ?></span>

                    <div class="cmd-hero__brief-grid">
                        <div class="cmd-hero__brief-item">
                            <strong class="cmd-hero__brief-value"><?= number_format($pendingCount) ?></strong>
                            <span class="cmd-hero__brief-name">Waiting in queue</span>
                        </div>
                        <div class="cmd-hero__brief-item">
                            <strong class="cmd-hero__brief-value"><?= number_format($reviewedTodayCount) ?></strong>
                            <span class="cmd-hero__brief-name">Reviewed today</span>
                        </div>
                        <div class="cmd-hero__brief-item">
                            <strong class="cmd-hero__brief-value"><?= number_format($recommendedByYouCount) ?></strong>
                            <span class="cmd-hero__brief-name">Recommended by you</span>
                        </div>
                    </div>
                </aside>
            </section>

            <section class="portfolio-section mb-8">
                <h3 class="portfolio-section__heading">Your evaluation desk</h3>
                <p class="portfolio-section__description">Workload and recommendation activity assigned to the evaluation role.</p>

                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <?php
                    $tiles = [
                        ['Total Applications', $totalApplications, 'All submissions', 'indigo', '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />'],
                        ['Pending Queue', $pendingCount, 'Waiting for review', 'amber', '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />'],
                        ['Evaluated by You', $evaluatedByYouCount, 'Completed assessments', 'teal', '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.03 0 1.9.693 2.166 1.638m-5.8 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664M6.75 7.5h10.5a2.25 2.25 0 0 1 2.25 2.25v10.5a2.25 2.25 0 0 1-2.25 2.25H6.75a2.25 2.25 0 0 1-2.25-2.25V9.75A2.25 2.25 0 0 1 6.75 7.5Z" />'],
                        ['Recommended by You', $recommendedByYouCount, 'Positive recommendations', 'emerald', '<path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.563.563 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />'],
                    ];
                    foreach ($tiles as [$label, $value, $desc, $tone, $iconPath]): ?>
                        <div class="stat-card stat-card--<?= $tone ?>">
                            <span class="stat-card__icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><?= $iconPath ?></svg>
                            </span>
                            <p class="stat-card__label"><?= evaluator_e($label) ?></p>
                            <p class="stat-card__value"><?= number_format($value) ?></p>
                            <p class="stat-card__desc"><?= evaluator_e($desc) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <div class="mb-8 chart-card">
                <div class="chart-card__header">
                    <p class="chart-card__heading">Submission activity</p>
                    <p class="chart-card__description">Six-month application intake trend for workload planning.</p>
                </div>
                <div class="chart-card__body" style="position: relative; height: 280px;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="chart-card">
                    <div class="chart-card__header">
                        <p class="chart-card__heading">Position workload</p>
                        <p class="chart-card__description">Application volume currently associated with each position.</p>
                    </div>
                    <div class="chart-card__body" style="position: relative; height: 300px;">
                        <canvas id="positionChart"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <div class="chart-card__header">
                        <p class="chart-card__heading">Queue by status</p>
                        <p class="chart-card__description">Current applications across the evaluation and decision stages.</p>
                    </div>
                    <div class="chart-card__body" style="position: relative; height: 300px;">
                        <canvas id="queueChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <div class="chart-card">
                    <div class="chart-card__header">
                        <p class="chart-card__heading">Latest applications</p>
                        <p class="chart-card__description">New records that may require evaluator review.</p>
                    </div>

                    <div class="flex justify-end border-b border-slate-200 px-4 py-3 sm:px-6">
                        <div class="relative w-full max-w-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                            <input type="search" id="latest-search" placeholder="Search" class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm outline-none focus:border-evaluator-indigo focus:ring-2 focus:ring-evaluator-indigo/20">
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-bold text-slate-600 sm:px-6">Control #</th>
                                    <th class="px-4 py-3 text-left font-bold text-slate-600 sm:px-6">Applicant</th>
                                    <th class="px-4 py-3 text-left font-bold text-slate-600 sm:px-6">Position</th>
                                    <th class="px-4 py-3 text-left font-bold text-slate-600 sm:px-6">Status</th>
                                    <th class="px-4 py-3 text-left font-bold text-slate-600 sm:px-6">Applied</th>
                                </tr>
                            </thead>
                            <tbody id="latest-tbody" class="divide-y divide-slate-100">
                                <?php if (empty($latestPending)): ?>
                                    <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400 sm:px-6">No pending applications.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($latestPending as $app): ?>
                                    <tr class="latest-row view-trigger cursor-pointer hover:bg-slate-50" data-search="<?= evaluator_e(mb_strtolower($app->control_number.' '.$app->full_name.' '.$app->email.' '.$app->job_title)) ?>" data-id="<?= $app->id ?>">
                                        <td class="px-4 py-3 font-mono text-xs sm:px-6"><?= evaluator_e($app->control_number ?: 'Not assigned') ?></td>
                                        <td class="px-4 py-3 sm:px-6">
                                            <p class="font-bold text-slate-800"><?= evaluator_e($app->full_name ?? '—') ?></p>
                                            <?php if ($app->email): ?><p class="text-xs text-slate-500"><?= evaluator_e($app->email) ?></p><?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600 sm:px-6"><?= evaluator_e($app->job_title ?? 'No position') ?></td>
                                        <td class="px-4 py-3 sm:px-6"><span class="pill bg-slate-100 text-slate-600"><?= evaluator_e($app->status) ?></span></td>
                                        <td class="px-4 py-3 text-slate-500 sm:px-6"><?= evaluator_e(date('M d, Y', strtotime($app->created_at))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-card__header">
                        <p class="chart-card__heading">Your recent evaluations</p>
                        <p class="chart-card__description">Latest assessments completed using your evaluator account.</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-bold text-slate-600 sm:px-6">Applicant</th>
                                    <th class="px-4 py-3 text-left font-bold text-slate-600 sm:px-6">Position</th>
                                    <th class="px-4 py-3 text-center font-bold text-slate-600 sm:px-6">Recommended</th>
                                    <th class="px-4 py-3 text-left font-bold text-slate-600 sm:px-6">Evaluated</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($recentEvaluations)): ?>
                                    <tr><td colspan="4" class="px-4 py-10 text-center text-slate-400 sm:px-6">No evaluations yet.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($recentEvaluations as $ev): ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-3 font-bold text-slate-800 sm:px-6"><?= evaluator_e($ev->full_name ?? '—') ?></td>
                                        <td class="px-4 py-3 text-slate-600 sm:px-6"><?= evaluator_e($ev->job_title ?? '—') ?></td>
                                        <td class="px-4 py-3 text-center sm:px-6"><?= eval_bool($ev->recommended) ? '<span class="text-emerald-600">&#10003;</span>' : '<span class="text-rose-500">&#10007;</span>' ?></td>
                                        <td class="px-4 py-3 text-slate-500 sm:px-6"><?= evaluator_e(date('M d, Y h:i A', strtotime($ev->evaluated_at))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<div id="view-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4 sm:p-6">
    <div class="flex h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <h3 class="text-lg font-black text-evaluator-indigoDark">Application Details</h3>
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
            frame.src = '<?= evaluator_url('view.php') ?>?id=' + encodeURIComponent(id) + '&embed=1';
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
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($trendLabels) ?>,
            datasets: [{
                label: 'Total applications',
                data: <?= json_encode($trendData) ?>,
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.12)',
                borderWidth: 2.5,
                tension: 0.35,
                fill: true,
                pointBackgroundColor: '#4f46e5',
                pointRadius: 4,
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(148, 163, 184, 0.18)' } }
            }
        }
    });

    new Chart(document.getElementById('positionChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_map(fn ($j) => $j->title, $perJob)) ?>,
            datasets: [{
                label: 'Applications',
                data: <?= json_encode(array_map(fn ($j) => (int) $j->total, $perJob)) ?>,
                backgroundColor: '#4f46e5',
                borderRadius: 7,
                barPercentage: 0.6,
                categoryPercentage: 0.8,
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(148, 163, 184, 0.18)' } }
            }
        }
    });

    new Chart(document.getElementById('queueChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($queueLabels) ?>,
            datasets: [{
                label: 'Applications',
                data: <?= json_encode($queueData) ?>,
                backgroundColor: <?= json_encode($queueColors) ?>,
                borderRadius: 7,
                barPercentage: 0.6,
                categoryPercentage: 0.8,
            }]
        },
        options: {
            indexAxis: 'y',
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(148, 163, 184, 0.18)' } },
                y: { grid: { display: false } }
            }
        }
    });

    (function () {
        var search = document.getElementById('latest-search');
        if (!search) return;
        search.addEventListener('input', function () {
            var term = search.value.trim().toLowerCase();
            document.querySelectorAll('.latest-row').forEach(function (row) {
                row.classList.toggle('hidden', term !== '' && row.dataset.search.indexOf(term) === -1);
            });
        });
    })();
</script>

<?php require __DIR__.'/_layout_footer.php'; ?>
