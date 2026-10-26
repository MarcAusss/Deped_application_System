<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

admin_require_login();
$pdo = portal_pdo();

$postings = $pdo->query(
    "SELECT * FROM job_positions
     WHERE posted_at IS NOT NULL
       AND (is_open = 0 OR (`until` IS NOT NULL AND TIMESTAMP(`until`, COALESCE(until_time, '23:59:59')) < NOW()))
     ORDER BY updated_at DESC"
)->fetchAll();

$pageTitle = 'Closed Postings | Administration';
require __DIR__.'/_layout_head.php';
?>

<?php $activePage = 'closed-job-postings'; require __DIR__.'/_layout_topbar.php'; ?>

<div class="lg:flex lg:flex-1">
    <?php require __DIR__.'/_layout_sidebar.php'; ?>

    <div class="min-w-0 flex-1">
        <main class="px-4 py-8 sm:px-6 lg:px-10">

            <?php if (flash_get('success')): ?>
                <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800"><?= admin_e(flash_get('success')) ?></div>
            <?php endif; ?>

            <div class="mb-6 border-b border-slate-200 pb-6">
                <h2 class="mt-2 text-3xl font-black text-admin-indigoDark">Closed Postings</h2>
                <p class="mt-2 text-slate-600">Positions that were posted but are no longer open.</p>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex justify-end border-b border-slate-200 px-4 py-3 sm:px-6">
                    <div class="relative w-full max-w-xs">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <input type="search" id="closed-postings-search" placeholder="Search" class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm outline-none focus:border-admin-indigo focus:ring-2 focus:ring-admin-indigo/20">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">JP Number</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Title</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Until</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Reason</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">References</th>
                                <th class="px-4 py-3 text-center font-bold text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="closed-postings-tbody" class="divide-y divide-slate-100">
                            <?php if (empty($postings)): ?>
                                <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">No closed postings.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($postings as $p):
                                $attachments = json_decode($p->attachment_paths ?? '[]', true) ?: [];
                                $deadlinePassed = admin_job_deadline_passed($p->until, $p->until_time);
                            ?>
                                <tr class="closed-posting-row hover:bg-slate-50" data-search="<?= admin_e(mb_strtolower($p->jp_number.' '.$p->title)) ?>">
                                    <td class="px-4 py-3 font-mono text-xs"><?= admin_e($p->jp_number ?: '—') ?></td>
                                    <td class="px-4 py-3 font-bold text-slate-800"><?= admin_e($p->title) ?></td>
                                    <td class="px-4 py-3 text-slate-600"><?= $p->until ? admin_e(date('M d, Y', strtotime($p->until))) : '—' ?></td>
                                    <td class="px-4 py-3"><span class="pill <?= $deadlinePassed ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600' ?>"><?= $deadlinePassed ? 'Deadline passed' : 'Closed manually' ?></span></td>
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
                                        <button type="button" class="repost-trigger font-bold text-admin-indigo hover:underline" data-id="<?= $p->id ?>">Repost</button>
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

<div id="repost-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4 sm:p-6">
    <div class="max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-8 py-5">
            <h3 class="text-xl font-black text-admin-indigoDark">Repost Job Position</h3>
            <button type="button" id="repost-close" class="text-slate-400 transition hover:text-slate-700" aria-label="Close">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-7 w-7">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form method="POST" action="<?= admin_url('job-position-publish.php') ?>" enctype="multipart/form-data" id="repost-form" class="grid gap-5 px-8 py-6 sm:grid-cols-3">
            <?= admin_csrf_field() ?>
            <input type="hidden" name="id" id="repost-field-id" value="">
            <input type="hidden" name="return" value="closed-job-postings">
            <input type="hidden" name="ajax" value="1">

            <div id="repost-errors" class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 sm:col-span-3"></div>

            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Title <span class="text-rose-500">*</span></label>
                <input type="text" name="title" id="repost-field-title" required class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-base">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Acronym</label>
                <input type="text" name="abbreviation" id="repost-field-abbreviation" placeholder="e.g. T-I" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-base">
            </div>

            <div class="sm:col-span-3">
                <label class="mb-1.5 block text-sm font-bold text-slate-700">No. of Vacancies <span class="text-rose-500">*</span></label>
                <div class="flex max-w-xs items-center gap-2">
                    <input type="number" name="slots" id="repost-field-slots" min="1" value="1" required class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-base">
                    <span class="shrink-0 text-sm text-slate-500">slot(s)</span>
                </div>
            </div>

            <div class="sm:col-span-3">
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Description <span class="text-rose-500">*</span></label>
                <textarea name="description" id="repost-field-description" rows="3" required class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-base"></textarea>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Posted</label>
                <input type="date" name="posted_at" id="repost-field-posted_at" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-base">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Until</label>
                <input type="date" name="until" id="repost-field-until" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-base">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Closing Time</label>
                <input type="text" name="until_time" id="repost-field-until_time" placeholder="e.g. 5:00 PM, 12 midnight, or 12 noon" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-base">
            </div>

            <?php $postingModeId = 'repost-posting-mode'; require __DIR__.'/_posting_mode_field.php'; ?>

            <?php $evaluatedByValue = 'evaluator'; $evaluatedByIdPrefix = 'repost-evaluated-by'; require __DIR__.'/_evaluated_by_field.php'; ?>

            <div class="sm:col-span-3">
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Salary Grade</label>
                <input type="text" name="salary_grade" id="repost-field-salary_grade" placeholder="e.g. 11" class="w-full max-w-xs rounded-lg border border-slate-300 px-4 py-2.5 text-base">
            </div>

            <div class="sm:col-span-3">
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Monthly Salary</label>
                <div class="flex items-center gap-2"><span class="text-base text-slate-500">₱</span><input type="number" step="0.01" name="monthly_salary" id="repost-field-monthly_salary" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-base"></div>
            </div>

            <div class="sm:col-span-3">
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Education Requirement</label>
                <textarea name="education_requirement" id="repost-field-education_requirement" rows="2" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-base"></textarea>
            </div>
            <div class="sm:col-span-3">
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Training Requirement</label>
                <textarea name="training_requirement" id="repost-field-training_requirement" rows="2" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-base"></textarea>
            </div>
            <div class="sm:col-span-3">
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Minimum Training Hours (QS)</label>
                <div class="flex max-w-xs items-center gap-2">
                    <input type="number" min="0" name="min_training_hours" id="repost-field-min_training_hours" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-base">
                    <span class="shrink-0 text-sm text-slate-500">hour(s)</span>
                </div>
            </div>

            <div class="sm:col-span-3">
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Experience Requirement</label>
                <textarea name="experience_requirement" id="repost-field-experience_requirement" rows="2" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-base"></textarea>
            </div>
            <div class="sm:col-span-3">
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Minimum Years of Experience (QS)</label>
                <div class="flex max-w-xs items-center gap-2">
                    <input type="number" min="0" step="0.01" name="min_experience_years" id="repost-field-min_experience_years" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-base">
                    <span class="shrink-0 text-sm text-slate-500">year(s)</span>
                </div>
            </div>

            <div class="sm:col-span-3">
                <label class="mb-1.5 block text-sm font-bold text-slate-700">Eligibility Requirement</label>
                <textarea name="eligibility_requirement" id="repost-field-eligibility_requirement" rows="2" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-base"></textarea>
            </div>

            <div class="sm:col-span-3">
                <label class="mb-1.5 block text-sm font-bold text-slate-700">D.M Notice of Vacancy</label>
                <ul id="repost-existing-attachments" class="mb-2 space-y-1"></ul>
                <div class="repost-dropzone relative rounded-lg border-2 border-dashed border-slate-300 px-4 py-6 text-center transition" data-input="repost-attachment-input">
                    <input type="file" name="attachment_paths[]" id="repost-attachment-input" multiple accept="application/pdf" class="absolute inset-0 h-full w-full cursor-pointer opacity-0">
                    <p class="pointer-events-none text-sm text-slate-500">Drag &amp; Drop your files or <span class="font-bold text-admin-indigo underline">Browse</span></p>
                </div>
                <p class="mt-2 text-xs text-slate-500">Upload the official D.M Notice of Vacancy. You can select or drag in multiple PDF files (or an entire folder of PDFs) at once. Applicants will be able to download each of these from the job listing.</p>
                <ul id="repost-attachment-selected" class="mt-2 space-y-1 text-sm text-slate-600"></ul>
            </div>

            <div class="sm:col-span-3">
                <label class="mb-1.5 block text-sm font-bold text-slate-700">CSC Publication of Vacancy</label>
                <ul id="repost-existing-csc" class="mb-2 space-y-1"></ul>
                <div class="repost-dropzone relative rounded-lg border-2 border-dashed border-slate-300 px-4 py-6 text-center transition" data-input="repost-csc-input">
                    <input type="file" name="csc_publication_paths[]" id="repost-csc-input" multiple accept="application/pdf" class="absolute inset-0 h-full w-full cursor-pointer opacity-0">
                    <p class="pointer-events-none text-sm text-slate-500">Drag &amp; Drop your files or <span class="font-bold text-admin-indigo underline">Browse</span></p>
                </div>
                <p class="mt-2 text-xs text-slate-500">Upload the official CSC Publication of Vacancy. You can select or drag in multiple PDF files (or an entire folder of PDFs) at once. Applicants will be able to download each of these from the job listing.</p>
                <ul id="repost-csc-selected" class="mt-2 space-y-1 text-sm text-slate-600"></ul>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-5 sm:col-span-3">
                <button type="button" id="repost-cancel" class="rounded-lg px-5 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                <button type="submit" id="repost-submit" class="rounded-lg bg-emerald-600 px-6 py-3 text-sm font-bold text-white transition hover:bg-emerald-700">Repost</button>
            </div>
        </form>
    </div>
</div>

<script>
    var REPOST_POSITIONS = <?= json_encode(array_map(function ($p) {
        return [
            'id' => $p->id,
            'title' => $p->title,
            'abbreviation' => $p->abbreviation,
            'slots' => (int) $p->slots,
            'description' => $p->description,
            'posted_at' => $p->posted_at ? substr((string) $p->posted_at, 0, 10) : date('Y-m-d'),
            'jp_number' => $p->jp_number,
            'evaluated_by' => $p->evaluated_by,
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
            'attachment_paths' => json_decode($p->attachment_paths ?? '[]', true) ?: [],
            'csc_publication_paths' => json_decode($p->csc_publication_paths ?? '[]', true) ?: [],
        ];
    }, $postings)) ?>;

    (function () {
        var modal = document.getElementById('repost-modal');
        var closeBtn = document.getElementById('repost-close');
        var cancelBtn = document.getElementById('repost-cancel');
        if (!modal) return;

        var fieldMap = ['title', 'abbreviation', 'slots', 'description', 'posted_at', 'until', 'until_time', 'salary_grade', 'monthly_salary', 'education_requirement', 'training_requirement', 'min_training_hours', 'experience_requirement', 'min_experience_years', 'eligibility_requirement'];

        function renderExistingFiles(listId, removeFieldName, paths) {
            var list = document.getElementById(listId);
            list.innerHTML = '';
            paths.forEach(function (path) {
                var li = document.createElement('li');
                li.className = 'file-remove-row flex items-center gap-2 text-sm';

                var checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.name = removeFieldName;
                checkbox.value = path;
                checkbox.className = 'file-remove-checkbox hidden';

                var link = document.createElement('a');
                link.href = '/admin-portal/document.php?path=' + encodeURIComponent(path);
                link.target = '_blank';
                link.className = 'file-remove-link text-admin-indigo underline';
                link.textContent = path.split('/').pop();

                var toggle = document.createElement('button');
                toggle.type = 'button';
                toggle.className = 'file-remove-toggle font-bold text-rose-600 hover:underline';
                toggle.textContent = 'Remove';
                toggle.addEventListener('click', function () {
                    var marked = checkbox.checked;
                    checkbox.checked = !marked;
                    link.classList.toggle('line-through', !marked);
                    link.classList.toggle('text-slate-400', !marked);
                    link.classList.toggle('text-admin-indigo', marked);
                    toggle.textContent = marked ? 'Remove' : 'Undo';
                    toggle.classList.toggle('text-rose-600', marked);
                    toggle.classList.toggle('text-slate-500', !marked);
                });

                li.appendChild(checkbox);
                li.appendChild(link);
                li.appendChild(toggle);
                list.appendChild(li);
            });
        }

        function resetSelectedPreview(inputId, previewId) {
            var input = document.getElementById(inputId);
            var preview = document.getElementById(previewId);
            input.value = '';
            preview.innerHTML = '';
        }

        function openModal(position) {
            document.getElementById('repost-field-id').value = position.id;
            fieldMap.forEach(function (field) {
                var el = document.getElementById('repost-field-' + field);
                if (el) el.value = position[field] !== null ? position[field] : '';
            });

            adminPostingMode('repost-posting-mode', position.jp_number, document.getElementById('repost-field-posted_at'));

            var evaluatedBy = position.evaluated_by === 'admin' ? 'admin' : 'evaluator';
            document.querySelectorAll('#repost-form .evaluated-by-input').forEach(function (radio) {
                radio.checked = radio.value === evaluatedBy;
            });

            renderExistingFiles('repost-existing-attachments', 'remove_attachment_paths[]', position.attachment_paths || []);
            renderExistingFiles('repost-existing-csc', 'remove_csc_publication_paths[]', position.csc_publication_paths || []);
            resetSelectedPreview('repost-attachment-input', 'repost-attachment-selected');
            resetSelectedPreview('repost-csc-input', 'repost-csc-selected');

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.querySelectorAll('.repost-trigger').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var position = REPOST_POSITIONS.find(function (p) { return String(p.id) === btn.dataset.id; });
                if (position) openModal(position);
            });
        });

        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal(); });

        function wireDropzone(dropzoneEl, previewId) {
            var input = dropzoneEl.querySelector('input[type="file"]');
            var preview = document.getElementById(previewId);

            function renderPreview() {
                preview.innerHTML = '';
                Array.prototype.forEach.call(input.files, function (file) {
                    var li = document.createElement('li');
                    li.textContent = file.name;
                    preview.appendChild(li);
                });
            }

            input.addEventListener('change', renderPreview);

            ['dragover', 'dragleave', 'drop'].forEach(function (evtName) {
                dropzoneEl.addEventListener(evtName, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzoneEl.classList.toggle('border-admin-indigo', evtName === 'dragover');
                    dropzoneEl.classList.toggle('bg-admin-indigoLight', evtName === 'dragover');
                });
            });

            dropzoneEl.addEventListener('drop', function (e) {
                if (e.dataTransfer && e.dataTransfer.files.length) {
                    input.files = e.dataTransfer.files;
                    renderPreview();
                }
            });
        }

        document.querySelectorAll('.repost-dropzone').forEach(function (zone) { wireDropzone(zone, zone.dataset.input === 'repost-attachment-input' ? 'repost-attachment-selected' : 'repost-csc-selected'); });

        var form = document.getElementById('repost-form');
        var submitBtn = document.getElementById('repost-submit');
        var errorBox = document.getElementById('repost-errors');

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

<script>
    (function () {
        var search = document.getElementById('closed-postings-search');
        if (!search) return;
        search.addEventListener('input', function () {
            var term = search.value.trim().toLowerCase();
            document.querySelectorAll('.closed-posting-row').forEach(function (row) {
                row.classList.toggle('hidden', term !== '' && row.dataset.search.indexOf(term) === -1);
            });
        });
    })();
</script>

<?php require __DIR__.'/_layout_footer.php'; ?>
