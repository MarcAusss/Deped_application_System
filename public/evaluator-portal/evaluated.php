<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

evaluator_require_login();
$user = evaluator_user();

$jobFilter = $_GET['job'] ?? '';

// Excluded applications are also awaiting the admin's final decision, same
// as evaluated ones — they belong on this list too, not just status =
// 'evaluated'.
$where = ["a.status IN ('evaluated', 'excluded')"];
$params = [];

if ($jobFilter !== '' && ctype_digit((string) $jobFilter)) {
    $where[] = 'a.job_position_id = :job';
    $params['job'] = $jobFilter;
}

$sql = 'SELECT a.id, a.status, a.created_at,
               jp.title AS job_title, jp.is_open, jp.until, jp.until_time,
               cn.control_number,
               p.full_name, p.email,
               e.evaluated_at, e.recommended, e.evaluator_id, e.result
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
$positionGroups = eval_group_by_position($applications);

$pageTitle = 'Evaluated | Evaluation Desk';
require __DIR__.'/_layout_head.php';
?>

<?php $activePage = 'evaluated'; require __DIR__.'/_layout_topbar.php'; ?>

<div class="lg:flex lg:flex-1">
    <?php require __DIR__.'/_layout_sidebar.php'; ?>

    <div class="min-w-0 flex-1">
        <main class="px-4 py-8 sm:px-6 lg:px-10">

            <?php if (flash_get('success')): ?>
                <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800"><?= evaluator_e(flash_get('success')) ?></div>
            <?php endif; ?>

            <div class="mb-6 flex flex-wrap items-end justify-between gap-3 border-b border-slate-200 pb-6">
                <div>
                    <div class="mb-1 flex items-center gap-1.5 text-xs font-bold text-slate-400">
                        <span>Applications</span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3 w-3"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                        <span class="text-slate-600">Evaluated</span>
                    </div>
                    <h2 class="text-3xl font-black text-evaluator-indigoDark">Evaluated</h2>
                    <p class="mt-1 text-sm text-slate-500">Applications you've already evaluated — awaiting the admin's final Qualified/Disqualified decision.</p>
                </div>

                <form method="POST" action="<?= evaluator_url('ier-preview.php') ?>">
                    <?= evaluator_csrf_field() ?>
                    <input type="hidden" name="source" value="filtered">
                    <input type="hidden" name="status" value="evaluated_excluded">
                    <input type="hidden" name="job" value="<?= evaluator_e((string) $jobFilter) ?>">
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-700">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 6.75-3-3m3 3 3-3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" /></svg>
                        Preview &amp; Export IER
                    </button>
                </form>
            </div>

            <form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
                <div class="relative">
                    <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Position</label>
                    <input type="hidden" name="job" id="job-filter-value" value="<?= evaluator_e((string) $jobFilter) ?>">
                    <button type="button" id="job-filter-trigger" class="flex w-60 items-center justify-between rounded-lg border border-slate-300 px-3 py-2 text-left text-sm outline-none focus:border-evaluator-indigo focus:ring-2 focus:ring-evaluator-indigo/20">
                        <span id="job-filter-trigger-label" class="truncate"><?php
                            $selectedJobTitle = 'All Positions';
                            foreach ($jobs as $job) {
                                if ((string) $job->id === (string) $jobFilter) { $selectedJobTitle = $job->title; break; }
                            }
                            echo evaluator_e($selectedJobTitle);
                        ?></span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4 shrink-0 text-slate-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>

                    <div id="job-filter-panel" class="absolute z-10 mt-1 hidden w-72 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                        <input type="text" id="job-filter-search" placeholder="Start typing to search…" class="w-full border-b border-slate-100 px-4 py-2.5 text-sm outline-none">
                        <ul id="job-filter-options" class="max-h-56 overflow-y-auto py-1 text-sm" role="listbox">
                            <li role="option" data-value="" data-label="All Positions" class="job-filter-option cursor-pointer px-4 py-2 hover:bg-evaluator-indigoLight">All Positions</li>
                            <?php foreach ($jobs as $job): ?>
                                <li role="option" data-value="<?= $job->id ?>" data-label="<?= evaluator_e($job->title) ?>" class="job-filter-option cursor-pointer px-4 py-2 hover:bg-evaluator-indigoLight"><?= evaluator_e($job->title) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div class="relative ml-auto w-full max-w-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input type="search" id="applications-search" placeholder="Search" class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm outline-none focus:border-evaluator-indigo focus:ring-2 focus:ring-evaluator-indigo/20">
                </div>
            </form>

            <form method="POST" action="<?= evaluator_url('ier-preview.php') ?>" id="bulk-export-form">
                <?= evaluator_csrf_field() ?>
                <input type="hidden" name="source" value="selected">

                <div class="mb-4 flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm font-bold text-slate-600">
                        <input type="checkbox" id="select-all">
                        Select all &middot; <?= count($applications) ?> application(s)
                    </label>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-bold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-40" id="export-selected-btn" disabled>
                        Preview &amp; Export Selected IER
                    </button>
                </div>

                <?php if (empty($applications)): ?>
                    <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-500 shadow-sm">No evaluated applications found.</div>
                <?php endif; ?>

                <?php foreach ($positionGroups as $positionTitle => $rows): ?>
                    <details class="position-group group mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" open>
                        <summary class="flex cursor-pointer select-none items-center justify-between gap-3 bg-slate-50 px-4 py-3">
                            <span class="font-black text-evaluator-indigoDark"><?= evaluator_e($positionTitle) ?></span>
                            <span class="pill bg-slate-200 text-slate-700"><?= count($rows) ?> application(s)</span>
                        </summary>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3"></th>
                                        <th class="px-4 py-3 text-left font-bold text-slate-600">Control #</th>
                                        <th class="px-4 py-3 text-left font-bold text-slate-600">Applicant Name</th>
                                        <th class="px-4 py-3 text-left font-bold text-slate-600">Email</th>
                                        <th class="px-4 py-3 text-left font-bold text-slate-600">Evaluated</th>
                                        <th class="px-4 py-3 text-center font-bold text-slate-600">Recommended</th>
                                        <th class="px-4 py-3 text-left font-bold text-slate-600">Status</th>
                                        <th class="px-4 py-3 text-left font-bold text-slate-600">Applied On</th>
                                        <th class="px-4 py-3 text-center font-bold text-slate-600">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($rows as $app): ?>
                                        <?php
                                            $claimedByOther = $app->evaluator_id && (int) $app->evaluator_id !== (int) $user->id;
                                            $canEdit = !$claimedByOther;
                                        ?>
                                        <tr class="app-row hover:bg-slate-50" data-search="<?= evaluator_e(mb_strtolower($app->control_number.' '.$app->full_name.' '.$app->email)) ?>">
                                            <td class="px-4 py-3"><input type="checkbox" name="ids[]" value="<?= $app->id ?>" class="row-check"></td>
                                            <td class="px-4 py-3 font-mono text-xs"><?= evaluator_e($app->control_number ?: 'Not assigned') ?></td>
                                            <td class="px-4 py-3 font-bold text-slate-800"><?= evaluator_e($app->full_name ?? '—') ?></td>
                                            <td class="px-4 py-3 text-slate-600"><?= evaluator_e($app->email ?? '—') ?></td>
                                            <td class="px-4 py-3 text-slate-500"><?= $app->evaluated_at ? evaluator_e(date('M d, Y h:i A', strtotime($app->evaluated_at))) : 'Not yet' ?></td>
                                            <td class="px-4 py-3 text-center">
                                                <?= eval_bool($app->recommended) ? '<span class="text-emerald-600">&#10003;</span>' : '<span class="text-rose-500">&#10007;</span>' ?>
                                            </td>
                                            <td class="px-4 py-3">
                                                <?php [$qsLabel, $qsClasses] = eval_qs_outcome_badge($app->result); ?>
                                                <span class="pill <?= $qsClasses ?>"><?= evaluator_e($qsLabel) ?></span>
                                            </td>
                                            <td class="px-4 py-3 text-slate-500"><?= evaluator_e(date('M d, Y', strtotime($app->created_at))) ?></td>
                                            <td class="px-4 py-3 text-center">
                                                <button type="button" class="view-trigger mr-2 font-bold text-slate-600 hover:text-evaluator-indigo" data-id="<?= $app->id ?>">View</button>
                                                <?php if ($canEdit): ?>
                                                    <a href="<?= evaluator_url('evaluate.php?id='.$app->id.'&from=evaluated') ?>" class="font-bold text-evaluator-indigo hover:underline">Edit</a>
                                                <?php else: ?>
                                                    <span class="cursor-not-allowed font-bold text-slate-300" title="Claimed by another evaluator">Edit</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </details>
                <?php endforeach; ?>
            </form>
        </main>
    </div>
</div>

<div id="view-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4 sm:p-6">
    <div class="flex h-[85vh] max-h-[calc(100vh-4rem)] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
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
        if (modal) {
            var openModal = function (id) {
                frame.src = '<?= evaluator_url('view.php') ?>?id=' + encodeURIComponent(id) + '&embed=1';
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };
            var closeModal = function () {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                frame.src = 'about:blank';
            };
            document.querySelectorAll('.view-trigger').forEach(function (btn) {
                btn.addEventListener('click', function () { openModal(btn.dataset.id); });
            });
            closeBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal(); });
        }
    })();
</script>

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

    (function () {
        var trigger = document.getElementById('job-filter-trigger');
        var panel = document.getElementById('job-filter-panel');
        var search = document.getElementById('job-filter-search');
        var optionsList = document.getElementById('job-filter-options');
        var hiddenValue = document.getElementById('job-filter-value');
        if (!trigger) return;

        function openPanel() {
            panel.classList.remove('hidden');
            trigger.classList.add('border-evaluator-indigo', 'ring-2', 'ring-evaluator-indigo/20');
            search.value = '';
            filterOptions('');
            search.focus();
        }

        function closePanel() {
            panel.classList.add('hidden');
            trigger.classList.remove('border-evaluator-indigo', 'ring-2', 'ring-evaluator-indigo/20');
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

<?php require __DIR__.'/_layout_footer.php'; ?>
