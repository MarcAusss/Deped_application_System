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
               p.full_name, p.email,
               e.result
        FROM applications a
        LEFT JOIN job_positions jp ON jp.id = a.job_position_id
        LEFT JOIN application_control_numbers cn ON cn.application_id = a.id
        LEFT JOIN applicant_profiles p ON p.application_id = a.id
        LEFT JOIN application_evaluations e ON e.application_id = a.id
        WHERE '.implode(' AND ', $where).'
        ORDER BY a.created_at DESC';

$stmt = portal_pdo()->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();

$jobs = portal_pdo()->query('SELECT id, title FROM job_positions ORDER BY title')->fetchAll();
$positionGroups = admin_group_by_position($applications);

$pageTitle = 'Applications | Administration';
require __DIR__.'/_layout_head.php';
?>

<?php $activePage = 'applications'; require __DIR__.'/_layout_topbar.php'; ?>

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
                <div class="relative">
                    <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Filter by Position</label>
                    <input type="hidden" name="job" id="job-filter-value" value="<?= admin_e((string) $jobFilter) ?>">
                    <button type="button" id="job-filter-trigger" class="flex w-60 items-center justify-between rounded-lg border border-slate-300 px-3 py-2 text-left text-sm outline-none focus:border-admin-indigo focus:ring-2 focus:ring-admin-indigo/20">
                        <span id="job-filter-trigger-label" class="truncate"><?php
                            $selectedJobTitle = 'All Positions';
                            foreach ($jobs as $job) {
                                if ((string) $job->id === (string) $jobFilter) { $selectedJobTitle = $job->title; break; }
                            }
                            echo admin_e($selectedJobTitle);
                        ?></span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4 shrink-0 text-slate-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>

                    <div id="job-filter-panel" class="absolute z-10 mt-1 hidden w-72 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                        <input type="text" id="job-filter-search" placeholder="Start typing to search…" class="w-full border-b border-slate-100 px-4 py-2.5 text-sm outline-none">
                        <ul id="job-filter-options" class="max-h-56 overflow-y-auto py-1 text-sm" role="listbox">
                            <li role="option" data-value="" data-label="All Positions" class="job-filter-option cursor-pointer px-4 py-2 hover:bg-admin-indigoLight">All Positions</li>
                            <?php foreach ($jobs as $job): ?>
                                <li role="option" data-value="<?= $job->id ?>" data-label="<?= admin_e($job->title) ?>" class="job-filter-option cursor-pointer px-4 py-2 hover:bg-admin-indigoLight"><?= admin_e($job->title) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div class="relative ml-auto w-full max-w-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input type="search" id="applications-search" placeholder="Search" class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm outline-none focus:border-admin-indigo focus:ring-2 focus:ring-admin-indigo/20">
                </div>
            </form>

            <?php if (empty($applications)): ?>
                <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-500 shadow-sm">No applications found.</div>
            <?php endif; ?>

            <?php foreach ($positionGroups as $positionTitle => $rows): ?>
                <details class="position-group group mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" open>
                    <summary class="flex cursor-pointer select-none items-center justify-between gap-3 bg-slate-50 px-4 py-3">
                        <span class="font-black text-admin-indigoDark"><?= admin_e($positionTitle) ?></span>
                        <span class="pill bg-slate-200 text-slate-700"><?= count($rows) ?> application(s)</span>
                    </summary>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-bold text-slate-600">Control #</th>
                                    <th class="px-4 py-3 text-left font-bold text-slate-600">Applicant Name</th>
                                    <th class="px-4 py-3 text-left font-bold text-slate-600">Email</th>
                                    <th class="px-4 py-3 text-left font-bold text-slate-600">Status</th>
                                    <th class="px-4 py-3 text-left font-bold text-slate-600">Applied On</th>
                                    <th class="px-4 py-3 text-center font-bold text-slate-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($rows as $app): $canDecide = in_array($app->status, ['evaluated', 'excluded'], true); ?>
                                    <tr class="app-row hover:bg-slate-50" data-search="<?= admin_e(mb_strtolower($app->control_number.' '.$app->full_name.' '.$app->email)) ?>">
                                        <td class="px-4 py-3 font-mono text-xs"><?= admin_e($app->control_number ?: 'Not assigned') ?></td>
                                        <td class="px-4 py-3 font-bold text-slate-800"><?= admin_e($app->full_name ?? '—') ?></td>
                                        <td class="px-4 py-3 text-slate-600"><?= admin_e($app->email ?? '—') ?></td>
                                        <td class="px-4 py-3">
                                            <?php if ($app->status === 'pending'): ?>
                                                <span class="pill bg-slate-100 text-slate-600">Pending</span>
                                            <?php else: ?>
                                                <?php [$qsLabel, $qsClasses] = eval_qs_outcome_badge($app->result); ?>
                                                <span class="pill <?= $qsClasses ?>"><?= admin_e($qsLabel) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-slate-500"><?= admin_e(date('M d, Y', strtotime($app->created_at))) ?></td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <button type="button" class="view-trigger font-bold text-slate-600 hover:text-admin-indigo" data-id="<?= $app->id ?>">View</button>

                                                <form method="POST" action="<?= admin_url('application-status.php') ?>" data-confirm="Are you sure you want to mark this application Qualified? This finalizes the hiring decision.">
                                                    <?= admin_csrf_field() ?>
                                                    <input type="hidden" name="id" value="<?= $app->id ?>">
                                                    <input type="hidden" name="action" value="qualify">
                                                    <button type="submit" <?= $canDecide ? '' : 'disabled' ?> class="font-bold <?= $canDecide ? 'text-emerald-600 hover:underline' : 'cursor-not-allowed text-slate-300' ?>">Qualified</button>
                                                </form>

                                                <form method="POST" action="<?= admin_url('application-status.php') ?>" data-confirm="Are you sure you want to mark this application Disqualified? This application will be moved to archive.">
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
                </details>
            <?php endforeach; ?>
        </main>
    </div>
</div>

<div id="view-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4 sm:p-6">
    <div class="flex h-[85vh] max-h-[calc(100vh-4rem)] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
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

        document.querySelectorAll('.view-trigger').forEach(function (btn) {
            btn.addEventListener('click', function () { openModal(btn.dataset.id); });
        });

        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal(); });
    })();
</script>

<script>
    (function () {
        var trigger = document.getElementById('job-filter-trigger');
        var panel = document.getElementById('job-filter-panel');
        var search = document.getElementById('job-filter-search');
        var optionsList = document.getElementById('job-filter-options');
        var hiddenValue = document.getElementById('job-filter-value');
        if (!trigger) return;

        function openPanel() {
            panel.classList.remove('hidden');
            trigger.classList.add('border-admin-indigo', 'ring-2', 'ring-admin-indigo/20');
            search.value = '';
            filterOptions('');
            search.focus();
        }

        function closePanel() {
            panel.classList.add('hidden');
            trigger.classList.remove('border-admin-indigo', 'ring-2', 'ring-admin-indigo/20');
        }

        function filterOptions(term) {
            term = term.trim().toLowerCase();
            optionsList.querySelectorAll('.job-filter-option').forEach(function (li) {
                li.classList.toggle('hidden', term !== '' && li.dataset.label.toLowerCase().indexOf(term) === -1);
            });
        }

        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            panel.classList.contains('hidden') ? openPanel() : closePanel();
        });

        search.addEventListener('input', function () { filterOptions(search.value); });

        optionsList.addEventListener('click', function (e) {
            var li = e.target.closest('.job-filter-option');
            if (!li) return;
            hiddenValue.value = li.dataset.value;
            trigger.closest('form').submit();
        });

        document.addEventListener('click', function (e) {
            if (!panel.contains(e.target) && !trigger.contains(e.target)) closePanel();
        });
    })();
</script>

<script>
    (function () {
        var search = document.getElementById('applications-search');
        if (!search) return;
        search.addEventListener('input', function () {
            var term = search.value.trim().toLowerCase();
            document.querySelectorAll('.position-group').forEach(function (group) {
                var anyVisible = false;
                group.querySelectorAll('.app-row').forEach(function (row) {
                    var matches = term === '' || row.dataset.search.indexOf(term) !== -1;
                    row.classList.toggle('hidden', !matches);
                    if (matches) anyVisible = true;
                });
                group.classList.toggle('hidden', !anyVisible);
            });
        });
    })();
</script>

<?php require __DIR__.'/_layout_footer.php'; ?>
