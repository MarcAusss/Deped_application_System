<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

admin_require_login();
$pdo = portal_pdo();

$postings = $pdo->query(
    "SELECT * FROM job_positions
     WHERE is_open = 1
       AND (`until` IS NULL OR TIMESTAMP(`until`, COALESCE(until_time, '23:59:59')) >= NOW())
     ORDER BY created_at DESC"
)->fetchAll();

$allPositions = $pdo->query('SELECT * FROM job_positions ORDER BY title')->fetchAll();

$pageTitle = 'Job Postings | Administration';
require __DIR__.'/_layout_head.php';
?>

<?php $activePage = 'job-postings'; require __DIR__.'/_layout_topbar.php'; ?>

<div class="lg:flex lg:flex-1">
    <?php require __DIR__.'/_layout_sidebar.php'; ?>

    <div class="min-w-0 flex-1">
        <main class="px-4 py-8 sm:px-6 lg:px-10">

            <?php if (flash_get('success')): ?>
                <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800"><?= admin_e(flash_get('success')) ?></div>
            <?php endif; ?>

            <div class="mb-6 flex flex-wrap items-end justify-between gap-3 border-b border-slate-200 pb-6">
                <div>
                    <h2 class="mt-2 text-3xl font-black text-admin-indigoDark">Job Postings</h2>
                    <p class="mt-2 text-slate-600">Positions currently advertised to applicants.</p>
                </div>

                <button type="button" id="manage-posting-open" class="rounded-lg bg-admin-indigo px-4 py-2 text-sm font-bold text-white hover:bg-admin-indigoDark">Manage</button>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex justify-end border-b border-slate-200 px-4 py-3 sm:px-6">
                    <div class="relative w-full max-w-xs">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <input type="search" id="job-postings-search" placeholder="Search" class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm outline-none focus:border-admin-indigo focus:ring-2 focus:ring-admin-indigo/20">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">JP Number</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Title</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Posted</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Until</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">References</th>
                                <th class="px-4 py-3 text-center font-bold text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="job-postings-tbody" class="divide-y divide-slate-100">
                            <?php if (empty($postings)): ?>
                                <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">No open postings.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($postings as $p): $attachments = json_decode($p->attachment_paths ?? '[]', true) ?: []; ?>
                                <tr class="job-posting-row hover:bg-slate-50" data-search="<?= admin_e(mb_strtolower($p->jp_number.' '.$p->title)) ?>">
                                    <td class="px-4 py-3 font-mono text-xs"><?= admin_e($p->jp_number ?: '—') ?></td>
                                    <td class="px-4 py-3 font-bold text-slate-800"><?= admin_e($p->title) ?></td>
                                    <td class="px-4 py-3 text-slate-600"><?= $p->posted_at ? admin_e(date('M d, Y', strtotime($p->posted_at))) : '—' ?></td>
                                    <td class="px-4 py-3 text-slate-600"><?= $p->until ? admin_e(date('M d, Y', strtotime($p->until))) : '—' ?></td>
                                    <td class="px-4 py-3">
                                        <?php if (empty($attachments)): ?>
                                            <span class="text-slate-400">—</span>
                                        <?php else: ?>
                                            <?php foreach ($attachments as $path): ?>
                                                <a href="<?= admin_url('document.php?path='.urlencode($path)) ?>" target="_blank" class="pill mb-1 mr-1 inline-block bg-admin-indigoLight text-admin-indigo"><?= admin_e(basename($path)) ?></a>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="<?= admin_url('job-position-edit.php?id='.$p->id.'&from=job-postings') ?>" class="font-bold text-admin-indigo hover:underline">Edit</a>
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

<div id="manage-posting-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4 sm:p-6">
    <div class="max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-8 py-5">
            <h3 class="text-xl font-black text-admin-indigoDark">Manage Job Position</h3>
            <button type="button" id="manage-posting-close" class="text-slate-400 transition hover:text-slate-700" aria-label="Close">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-7 w-7">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form method="POST" action="<?= admin_url('job-position-publish.php') ?>" id="manage-posting-form" class="grid gap-5 px-8 py-6 sm:grid-cols-3">
            <?= admin_csrf_field() ?>
            <input type="hidden" name="id" id="manage-field-id" value="">
            <input type="hidden" name="return" value="job-postings">
            <input type="hidden" name="ajax" value="1">

            <div id="manage-posting-errors" class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 sm:col-span-3"></div>

            <div class="relative sm:col-span-3">
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Select Position <span class="text-rose-500">*</span></label>
                <button type="button" id="manage-position-trigger" class="flex w-full items-center justify-between rounded-lg border border-slate-300 px-4 py-2.5 text-left text-base outline-none focus:border-admin-indigo focus:ring-2 focus:ring-admin-indigo/20" aria-haspopup="listbox" aria-expanded="false">
                    <span id="manage-position-trigger-label" class="text-slate-400">Select an option</span>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4 shrink-0 text-slate-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>

                <div id="manage-position-panel" class="absolute z-10 mt-1 hidden w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                    <input type="text" id="manage-position-search" placeholder="Start typing to search…" class="w-full border-b border-slate-100 px-4 py-2.5 text-sm outline-none">
                    <ul id="manage-position-options" class="max-h-56 overflow-y-auto py-1 text-sm" role="listbox">
                        <?php foreach ($allPositions as $p): ?>
                            <li role="option" data-id="<?= $p->id ?>" data-label="<?= admin_e($p->title) ?>" class="manage-position-option cursor-pointer px-4 py-2 hover:bg-admin-indigoLight"><?= admin_e($p->title) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Title <span class="text-rose-500">*</span></label>
                <input type="text" name="title" id="manage-field-title" required class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-base">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Acronym</label>
                <input type="text" name="abbreviation" id="manage-field-abbreviation" placeholder="e.g. T-I" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-base">
            </div>

            <div class="sm:col-span-3">
                <label class="mb-1.5 block text-sm font-bold text-slate-700">No. of Vacancies <span class="text-rose-500">*</span></label>
                <div class="flex max-w-xs items-center gap-2">
                    <input type="number" name="slots" id="manage-field-slots" min="1" value="1" required class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-base">
                    <span class="shrink-0 text-sm text-slate-500">slot(s)</span>
                </div>
            </div>

            <div class="sm:col-span-3">
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Description <span class="text-rose-500">*</span></label>
                <textarea name="description" id="manage-field-description" rows="3" required class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-base"></textarea>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Posted</label>
                <input type="date" name="posted_at" id="manage-field-posted_at" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-base">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Until</label>
                <input type="date" name="until" id="manage-field-until" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-base">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Closing Time</label>
                <input type="text" name="until_time" id="manage-field-until_time" placeholder="e.g. 5:00 PM, 12 midnight, or 12 noon" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-base">
            </div>

            <div class="sm:col-span-3">
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Salary Grade</label>
                <input type="text" name="salary_grade" id="manage-field-salary_grade" placeholder="e.g. 11" class="w-full max-w-xs rounded-lg border border-slate-300 px-4 py-2.5 text-base">
            </div>

            <div class="sm:col-span-3">
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Monthly Salary</label>
                <div class="flex items-center gap-2"><span class="text-base text-slate-500">₱</span><input type="number" step="0.01" name="monthly_salary" id="manage-field-monthly_salary" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-base"></div>
            </div>

            <div class="sm:col-span-3">
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Education Requirement</label>
                <textarea name="education_requirement" id="manage-field-education_requirement" rows="2" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-base"></textarea>
            </div>
            <div class="sm:col-span-3">
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Training Requirement</label>
                <textarea name="training_requirement" id="manage-field-training_requirement" rows="2" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-base"></textarea>
            </div>
            <div class="sm:col-span-3">
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Minimum Training Hours (QS)</label>
                <input type="number" min="0" name="min_training_hours" id="manage-field-min_training_hours" class="w-full max-w-xs rounded-lg border border-slate-300 px-4 py-2.5 text-base">
            </div>

            <div class="sm:col-span-3">
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Experience Requirement</label>
                <textarea name="experience_requirement" id="manage-field-experience_requirement" rows="2" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-base"></textarea>
            </div>
            <div class="sm:col-span-3">
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Minimum Years of Experience (QS)</label>
                <input type="number" min="0" step="0.01" name="min_experience_years" id="manage-field-min_experience_years" class="w-full max-w-xs rounded-lg border border-slate-300 px-4 py-2.5 text-base">
            </div>

            <div class="sm:col-span-3">
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Eligibility Requirement</label>
                <textarea name="eligibility_requirement" id="manage-field-eligibility_requirement" rows="2" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-base"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-5 sm:col-span-3">
                <button type="button" id="manage-posting-cancel" class="rounded-lg px-5 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                <button type="submit" id="manage-posting-submit" disabled class="rounded-lg bg-admin-indigo px-6 py-3 text-sm font-bold text-white transition hover:bg-admin-indigoDark disabled:cursor-not-allowed disabled:opacity-40">Post This Job Position</button>
            </div>
        </form>
    </div>
</div>

<script>
    var MANAGE_POSITIONS = <?= json_encode(array_map(function ($p) {
        return [
            'id' => $p->id,
            'title' => $p->title,
            'abbreviation' => $p->abbreviation,
            'slots' => (int) $p->slots,
            'description' => $p->description,
            'posted_at' => $p->posted_at ? substr((string) $p->posted_at, 0, 10) : date('Y-m-d'),
            'until' => $p->until ? substr((string) $p->until, 0, 10) : '',
            'until_time' => $p->until_time ? date('g:i A', strtotime($p->until_time)) : '',
            'salary_grade' => $p->salary_grade,
            'monthly_salary' => $p->monthly_salary,
            'education_requirement' => $p->education_requirement,
            'training_requirement' => $p->training_requirement,
            'min_training_hours' => $p->min_training_hours,
            'experience_requirement' => $p->experience_requirement,
            'min_experience_years' => $p->min_experience_years,
            'eligibility_requirement' => $p->eligibility_requirement,
        ];
    }, $allPositions)) ?>;

    (function () {
        var modal = document.getElementById('manage-posting-modal');
        var openBtn = document.getElementById('manage-posting-open');
        var closeBtn = document.getElementById('manage-posting-close');
        var cancelBtn = document.getElementById('manage-posting-cancel');
        var submitBtn = document.getElementById('manage-posting-submit');
        var trigger = document.getElementById('manage-position-trigger');
        var triggerLabel = document.getElementById('manage-position-trigger-label');
        var panel = document.getElementById('manage-position-panel');
        var search = document.getElementById('manage-position-search');
        var optionsList = document.getElementById('manage-position-options');
        if (!modal || !openBtn || !trigger) return;

        var fieldMap = ['title', 'abbreviation', 'slots', 'description', 'posted_at', 'until', 'until_time', 'salary_grade', 'monthly_salary', 'education_requirement', 'training_requirement', 'min_training_hours', 'experience_requirement', 'min_experience_years', 'eligibility_requirement'];

        function openModal() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            closePositionPanel();
        }

        openBtn.addEventListener('click', openModal);
        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal(); });

        function openPositionPanel() {
            panel.classList.remove('hidden');
            trigger.setAttribute('aria-expanded', 'true');
            trigger.classList.add('border-admin-indigo', 'ring-2', 'ring-admin-indigo/20');
            search.value = '';
            filterOptions('');
            search.focus();
        }

        function closePositionPanel() {
            panel.classList.add('hidden');
            trigger.setAttribute('aria-expanded', 'false');
            trigger.classList.remove('border-admin-indigo', 'ring-2', 'ring-admin-indigo/20');
        }

        function filterOptions(term) {
            term = term.trim().toLowerCase();
            optionsList.querySelectorAll('.manage-position-option').forEach(function (li) {
                li.classList.toggle('hidden', term !== '' && li.dataset.label.toLowerCase().indexOf(term) === -1);
            });
        }

        function selectPosition(id, label) {
            var position = MANAGE_POSITIONS.find(function (p) { return String(p.id) === String(id); });

            triggerLabel.textContent = label;
            triggerLabel.classList.remove('text-slate-400');
            document.getElementById('manage-field-id').value = position ? position.id : '';
            submitBtn.disabled = !position;

            fieldMap.forEach(function (field) {
                var el = document.getElementById('manage-field-' + field);
                if (el) el.value = position && position[field] !== null ? position[field] : '';
            });

            closePositionPanel();
        }

        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            panel.classList.contains('hidden') ? openPositionPanel() : closePositionPanel();
        });

        search.addEventListener('input', function () { filterOptions(search.value); });

        optionsList.addEventListener('click', function (e) {
            var li = e.target.closest('.manage-position-option');
            if (li) selectPosition(li.dataset.id, li.dataset.label);
        });

        document.addEventListener('click', function (e) {
            if (!panel.contains(e.target) && !trigger.contains(e.target)) closePositionPanel();
        });

        var form = document.getElementById('manage-posting-form');
        var errorBox = document.getElementById('manage-posting-errors');

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

    (function () {
        var search = document.getElementById('job-postings-search');
        if (!search) return;
        search.addEventListener('input', function () {
            var term = search.value.trim().toLowerCase();
            document.querySelectorAll('.job-posting-row').forEach(function (row) {
                row.classList.toggle('hidden', term !== '' && row.dataset.search.indexOf(term) === -1);
            });
        });
    })();
</script>

<?php require __DIR__.'/_layout_footer.php'; ?>
