<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

evaluator_require_login();

// When opened inside the Qualified/Disqualified "View" modal (an iframe),
// skip the portal chrome (topbar/sidebar/back link) and render just the
// content — a bare embeddable fragment instead of a full page.
$embed = ($_GET['embed'] ?? '') === '1';

$applicationId = (int) ($_GET['id'] ?? 0);
if (!$applicationId) {
    http_response_code(404);
    exit;
}

$stmt = portal_pdo()->prepare('SELECT * FROM applications WHERE id = ?');
$stmt->execute([$applicationId]);
$application = $stmt->fetch();
if (!$application) {
    http_response_code(404);
    exit;
}

$stmt = portal_pdo()->prepare('SELECT * FROM job_positions WHERE id = ?');
$stmt->execute([$application->job_position_id]);
$job = $stmt->fetch();

$stmt = portal_pdo()->prepare('SELECT * FROM applicant_profiles WHERE application_id = ?');
$stmt->execute([$applicationId]);
$profile = $stmt->fetch();

$stmt = portal_pdo()->prepare('SELECT control_number FROM application_control_numbers WHERE application_id = ?');
$stmt->execute([$applicationId]);
$controlNumberRow = $stmt->fetch();

$stmt = portal_pdo()->prepare(
    'SELECT e.*, u.name AS evaluator_name
     FROM application_evaluations e
     LEFT JOIN users u ON u.id = e.evaluator_id
     WHERE e.application_id = ?'
);
$stmt->execute([$applicationId]);
$evaluation = $stmt->fetch();

$fetchAll = function (string $table) use ($applicationId) {
    $stmt = portal_pdo()->prepare("SELECT * FROM {$table} WHERE application_id = ? ORDER BY id");
    $stmt->execute([$applicationId]);

    return $stmt->fetchAll();
};
$educations = $fetchAll('applicant_educations');
$experiences = $fetchAll('applicant_experiences');
$trainings = $fetchAll('applicant_trainings');
$eligibilities = $fetchAll('applicant_eligibilities');
$documents = $fetchAll('application_documents');
$documentsByType = [];
foreach ($documents as $doc) {
    $documentsByType[$doc->type] = $doc;
}

$mandatorySelected = $evaluation ? (json_decode($evaluation->documentary_mandatory ?? '[]', true) ?: []) : [];
$otherSelected = $evaluation ? (json_decode($evaluation->documentary_other ?? '[]', true) ?: []) : [];
$qsEducation = $evaluation ? eval_bool($evaluation->qs_education_met) : null;
$qsExperience = $evaluation ? eval_bool($evaluation->qs_experience_met) : null;
$qsTraining = $evaluation ? eval_bool($evaluation->qs_training_met) : null;
$qsEligibility = $evaluation ? eval_bool($evaluation->qs_eligibility_met) : null;
$documentaryComplete = eval_is_documentary_complete($mandatorySelected);

$result = $evaluation->result ?? EVAL_RESULT_PENDING;
$disqualifiedCategories = eval_disqualified_categories($qsEducation, $qsExperience, $qsTraining, $qsEligibility);
$missingMandatory = $result === EVAL_RESULT_EXCLUDED ? eval_missing_mandatory_requirements($mandatorySelected) : [];

$colorHex = [
    'success' => ['bg' => '#dcfce7', 'fg' => '#15803d', 'badge' => '#15803d'],
    'danger' => ['bg' => '#fee2e2', 'fg' => '#b91c1c', 'badge' => '#b91c1c'],
    'gray' => ['bg' => '#f1f5f9', 'fg' => '#475569', 'badge' => '#475569'],
];
$resultStyle = $colorHex[eval_result_color($result)];

$eduRequirementFallback = "Bachelor's Degree in Guidance Counseling or Psychology; or any Bachelor's Degree with atleast eighteen (18) units of courses in Guidance and Psychology; or Any  Bachelor's Degree with a minimum of eighteen (18) units of Behavioral Science courses that shall include 200 hours of supervised practicum or internship experience on guidance and counseling, preferably in a school or community setting";
$eligRequirementFallback = 'Career Service Professional, Second Level Eligibility and RA 1080.';

function met_badge(?bool $met): string
{
    $style = match ($met) {
        true => 'background:#15803d;color:#fff;',
        false => 'background:#b91c1c;color:#fff;',
        default => 'background:#f1f5f9;color:#475569;',
    };
    $label = match ($met) {
        true => 'Meet the QS',
        false => 'Did not Meet the QS',
        default => 'Not yet marked',
    };

    return '<span style="'.$style.'" class="inline-block rounded-md px-2.5 py-1 text-xs font-bold">'.evaluator_e($label).'</span>';
}

$pageTitle = ($profile->full_name ?? 'Application').' — View | Evaluation Desk';
require __DIR__.'/_layout_head.php';
?>

<?php if (!$embed): ?>
<?php $activePage = ''; require __DIR__.'/_layout_topbar.php'; ?>

<div class="lg:flex lg:flex-1">
    <?php require __DIR__.'/_layout_sidebar.php'; ?>

    <div class="min-w-0 flex-1">
<?php endif; ?>
        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
            <?php if (!$embed): ?>
            <a href="javascript:history.back()" class="mb-4 inline-flex items-center gap-1.5 text-sm font-bold text-slate-500 hover:text-evaluator-indigo">&larr; Back</a>
            <?php endif; ?>

            <h2 class="mb-6 text-2xl font-black text-evaluator-indigoDark"><?= evaluator_e($profile->full_name ?? 'Application Details') ?></h2>

            <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <?= evaluator_section_header('Application Info', 'briefcase') ?>
                <div class="grid gap-4 p-6 sm:grid-cols-2">
                    <div><p class="text-xs font-bold uppercase text-slate-500">Job Position</p><p class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= evaluator_e($job->title ?? '—') ?></p></div>
                    <div><p class="text-xs font-bold uppercase text-slate-500">Current Status</p><p class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= evaluator_e(ucfirst($application->status)) ?></p></div>
                </div>
            </section>

            <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <?= evaluator_section_header('Control Number', 'hashtag') ?>
                <div class="p-6">
                    <p class="text-xs font-bold uppercase text-slate-500">Assigned Control Number</p><p class="mt-1 text-sm"><?= evaluator_e($controlNumberRow->control_number ?? 'Not yet assigned') ?></p>
                </div>
            </section>

            <?php if ($evaluation): ?>
                <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <?= evaluator_section_header('Documentary Requirements', 'clipboard', 'Submitted by the evaluator. View only.') ?>
                    <div class="grid gap-4 p-6 sm:grid-cols-2">
                        <div>
                            <p class="mb-2 text-sm font-bold">Mandatory Requirements (<?= eval_count_selected(eval_mandatory_requirements(), $mandatorySelected) ?> of <?= eval_mandatory_count() ?>)</p>
                            <div class="grid gap-2">
                                <?php foreach (eval_mandatory_requirements() as $key => $label): ?>
                                    <?= evaluator_requirement_box($label, in_array($key, $mandatorySelected, true)) ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div>
                            <p class="mb-2 text-sm font-bold">Other Requirements (<?= eval_count_selected(eval_other_requirements(), $otherSelected) ?> of <?= eval_other_count() ?>)</p>
                            <div class="grid gap-2">
                                <?php foreach (eval_other_requirements() as $key => $label): ?>
                                    <?= evaluator_requirement_box($label, in_array($key, $otherSelected, true)) ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </section>

                <?php if ($documentaryComplete): ?>
                <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <?= evaluator_section_header('Qualification Standards', 'scale') ?>
                    <div class="grid gap-4 p-6 sm:grid-cols-2">
                        <?php
                        $qsBlocks = [
                            ["Bachelor's Degree", eval_applicant_bachelors_degree($educations), $job->education_requirement ?: $eduRequirementFallback, $qsEducation],
                            ['Years of Experience', eval_applicant_years_of_experience($experiences), ($job->min_experience_years ?? 0).' minimum year(s)', $qsExperience],
                            ['Hours of Training', eval_applicant_hours_of_training($trainings), ($job->min_training_hours ?? 0).' minimum hour(s)', $qsTraining],
                            ['Eligibility', eval_applicant_eligibility($eligibilities), $job->eligibility_requirement ?: $eligRequirementFallback, $qsEligibility],
                        ];
                        foreach ($qsBlocks as [$title, $applicantVal, $standardVal, $met]): ?>
                            <div class="rounded-xl border border-slate-200 p-4">
                                <p class="mb-2 font-black text-evaluator-indigoDark"><?= evaluator_e($title) ?></p>
                                <div class="mb-2 rounded-lg border border-slate-300 p-2 text-sm"><span class="block font-bold">Applicant</span><?= evaluator_e($applicantVal) ?></div>
                                <div class="mb-3 rounded-lg border border-slate-300 p-2 text-xs leading-relaxed"><span class="mb-0.5 block font-bold text-sm">Qualification Standard</span><?= evaluator_e($standardVal) ?></div>
                                <?= met_badge($met) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
            <?php endif; ?>

            <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <?= evaluator_section_header('Applicant Profile', 'user') ?>
                <div class="grid gap-4 p-6 sm:grid-cols-2">
                    <?php
                    $profileFields = [
                        'Full Name' => $profile->full_name ?? null, 'Email Address' => $profile->email ?? null,
                        'Phone Number' => $profile->phone ?? null, 'Address' => $profile->address ?? null,
                        'Birth Date' => isset($profile->birth_date) ? date('M d, Y', strtotime($profile->birth_date)) : null,
                        'Sex' => $profile->sex ?? null, 'Civil Status' => $profile->civil_status ?? null,
                        'Religion' => $profile->religion ?? null, 'Disability (if any)' => $profile->disability ?? null,
                        'Ethnic Group' => $profile->ethnic_group ?? null,
                    ];
                    foreach ($profileFields as $label => $value): ?>
                        <div class="rounded-lg border border-slate-300 p-2.5"><p class="text-sm font-bold text-slate-700"><?= evaluator_e($label) ?></p><span class="text-slate-600"><?= evaluator_e($value ?: '—') ?></span></div>
                    <?php endforeach; ?>
                </div>
            </section>

            <?php if ($evaluation): ?>
                <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <?= evaluator_section_header('Evaluation Result', 'flag') ?>
                    <div class="p-6">
                        <div class="mb-4 rounded-lg border-2 px-4 py-2" style="background:<?= $resultStyle['bg'] ?>;border-color:<?= $resultStyle['fg'] ?>;">
                            <span class="inline-block rounded-md px-2.5 py-1 text-sm font-bold text-white" style="background:<?= $resultStyle['badge'] ?>;"><?= evaluator_e(strtoupper(eval_result_label($result))) ?></span>
                            <span class="ml-2 text-xs font-bold" style="color:#1e3a8a;"><?= evaluator_e(eval_result_description($result, $disqualifiedCategories, $missingMandatory)) ?></span>
                        </div>
                        <?php if ($missingMandatory): ?>
                            <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-4">
                                <p class="mb-2 text-xs font-bold uppercase tracking-wide text-rose-700">Missing Mandatory Requirements</p>
                                <ul class="list-inside list-disc space-y-1 text-sm text-rose-700">
                                    <?php foreach ($missingMandatory as $item): ?>
                                        <li><?= evaluator_e($item) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <?php /* "Evaluated By" is intentionally not shown here — only Administrators can see which evaluator handled an application. */ ?>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div><p class="text-xs font-bold uppercase text-slate-500">Evaluated On</p><p class="mt-1 text-sm"><?= $evaluation->evaluated_at ? evaluator_e(date('M d, Y h:i A', strtotime($evaluation->evaluated_at))) : '—' ?></p></div>
                        </div>
                        <div class="mt-4">
                            <p class="mb-1 text-sm font-bold text-slate-700">Remarks / Notes</p>
                            <div class="rounded-lg border border-slate-300 p-3 text-sm text-slate-600"><?= nl2br(evaluator_e($evaluation->remarks ?? '—')) ?></div>
                        </div>
                    </div>
                </section>
            <?php else: ?>
                <section class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-6">
                    <h3 class="mb-2 text-lg font-black text-amber-800">Evaluation Pending</h3>
                    <p class="text-sm text-amber-700">This application has not been evaluated yet. Qualify/Disqualify will become available once the evaluator submits their checklist.</p>
                </section>
            <?php endif; ?>

            <?php
            $readonlyTables = [
                ['title' => 'Educational Background', 'icon' => 'academic-cap', 'headers' => ['Level', 'School / Institution', 'Degree / Course', 'Year Graduated'], 'rows' => array_map(fn ($e) => [
                    ($e->level === "Other's" && $e->level_specify) ? $e->level.' - '.$e->level_specify : $e->level,
                    $e->school ?: '—', $e->degree ?: '—', $e->year_graduated ?: '—',
                ], $educations)],
                ['title' => 'Work Experience', 'icon' => 'building', 'headers' => ['Job Title', 'Company', 'First Day', 'Last Day', 'Details'], 'rows' => array_map(fn ($e) => [
                    $e->title ?: '—', $e->company ?: '—', $e->first_day ?: 'Not provided', $e->last_day ?: 'Not provided', $e->details ?: 'Not provided',
                ], $experiences)],
                ['title' => 'Trainings & Seminars', 'icon' => 'presentation', 'headers' => ['Title', 'Hours', 'Start', 'End'], 'rows' => array_map(fn ($t) => [
                    $t->title ?: '—', ($t->hours ?: '0').' hrs',
                    $t->training_date ? date('F Y', strtotime($t->training_date)) : 'Not provided',
                    $t->training_end_date ? date('F Y', strtotime($t->training_end_date)) : 'Not provided',
                ], $trainings)],
                ['title' => 'Eligibilities / Licenses', 'icon' => 'id-card', 'headers' => ['License / Eligibility', 'Rating', 'Date Issued', 'Valid Until'], 'rows' => array_map(fn ($el) => [
                    (in_array($el->license_name, ['RA1080', "Other's"], true) && $el->license_specify) ? $el->license_name.' - '.$el->license_specify : $el->license_name,
                    $el->rating ?: '—',
                    $el->date_issued ? date('M d, Y', strtotime($el->date_issued)) : '—',
                    eval_bool($el->never_expires) ? 'Never Expires' : ($el->valid_until ? date('M d, Y', strtotime($el->valid_until)) : '—'),
                ], $eligibilities)],
            ];
            foreach ($readonlyTables as $t): ?>
                <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <?= evaluator_section_header($t['title'], $t['icon']) ?>
                    <div class="p-6">
                    <?php if (empty($t['rows'])): ?>
                        <p class="text-sm text-slate-500">No records submitted.</p>
                    <?php else: ?>
                        <div class="overflow-x-auto"><table class="w-full text-sm">
                            <thead><tr><?php foreach ($t['headers'] as $h): ?><th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold"><?= evaluator_e($h) ?></th><?php endforeach; ?></tr></thead>
                            <tbody><?php foreach ($t['rows'] as $row): ?><tr><?php foreach ($row as $cell): ?><td class="border-b border-slate-200 px-3 py-2"><?= evaluator_e((string) $cell) ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody>
                        </table></div>
                    <?php endif; ?>
                    </div>
                </section>
            <?php endforeach; ?>

            <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <?= evaluator_section_header('Submitted Documents', 'paperclip', null, count($documents) < 2 ? '' : '<button type="button" id="download-all-docs" title="If your browser blocks some files, click the download icon near the address bar and choose Allow — all documents will then download together." class="inline-flex items-center gap-1.5 rounded-lg bg-white/15 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-white/25"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>Download All</button>') ?>
                <div class="p-6">
                <?php if (count($documents) > 1): ?>
                    <p id="download-all-hint" class="mb-3 hidden rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-700">If your browser blocked some of the files, click the download icon near the address bar and choose "Allow" — all documents will then download together.</p>
                <?php endif; ?>
                    <div class="overflow-x-auto"><table class="w-full text-sm">
                        <thead><tr><th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Document Type</th><th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">File</th></tr></thead>
                        <tbody>
                        <?php foreach (DOCUMENT_FIELDS as $field => $label): $doc = $documentsByType[$field] ?? null; ?>
                        <tr>
                            <td class="border-b border-slate-200 px-3 py-2"><span class="pill bg-evaluator-indigoLight text-evaluator-indigo"><?= evaluator_e($label) ?></span></td>
                            <?php if ($doc): ?>
                            <td class="border-b border-slate-200 px-3 py-2"><button type="button" class="doc-view-trigger text-evaluator-indigo underline" data-url="<?= evaluator_e(evaluator_url('document.php?path='.urlencode($doc->file_path))) ?>" data-name="<?= evaluator_e(basename($doc->file_path)) ?>"><?= evaluator_e(basename($doc->file_path)) ?></button></td>
                            <?php else: ?>
                            <td class="border-b border-slate-200 px-3 py-2 text-slate-400 italic">No <?= evaluator_e($label) ?> attached</td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        <?php foreach ($documents as $doc): if (array_key_exists($doc->type, DOCUMENT_FIELDS)) { continue; } ?>
                        <tr>
                            <td class="border-b border-slate-200 px-3 py-2"><span class="pill bg-evaluator-indigoLight text-evaluator-indigo"><?= evaluator_e($doc->type) ?></span></td>
                            <td class="border-b border-slate-200 px-3 py-2"><button type="button" class="doc-view-trigger text-evaluator-indigo underline" data-url="<?= evaluator_e(evaluator_url('document.php?path='.urlencode($doc->file_path))) ?>" data-name="<?= evaluator_e(basename($doc->file_path)) ?>"><?= evaluator_e(basename($doc->file_path)) ?></button></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                </div>
            </section>
        </main>
<?php if (!$embed): ?>
    </div>
</div>
<?php endif; ?>

<div id="doc-view-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-2 sm:p-4">
    <div class="flex h-[97vh] w-full max-w-[98vw] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <h3 id="doc-view-modal-title" class="truncate text-lg font-black text-evaluator-indigoDark">Document</h3>
            <div class="flex items-center gap-4">
                <a id="doc-view-modal-newtab" href="#" target="_blank" rel="noopener" class="text-sm font-bold text-evaluator-indigo hover:underline">Open in new tab</a>
                <button type="button" id="doc-view-modal-close" class="text-slate-400 transition hover:text-slate-700" aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
        <iframe id="doc-view-modal-frame" class="h-full w-full flex-1" src="about:blank"></iframe>
    </div>
</div>

<script>
    (function () {
        var modal = document.getElementById('doc-view-modal');
        var closeBtn = document.getElementById('doc-view-modal-close');
        var frame = document.getElementById('doc-view-modal-frame');
        var titleEl = document.getElementById('doc-view-modal-title');
        var newTabLink = document.getElementById('doc-view-modal-newtab');
        if (!modal) return;

        function openModal(url, name) {
            titleEl.textContent = name || 'Document';
            newTabLink.href = url;
            frame.src = url;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            frame.src = 'about:blank';
        }

        document.querySelectorAll('.doc-view-trigger').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openModal(btn.getAttribute('data-url'), btn.getAttribute('data-name'));
            });
        });

        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
        });
    })();
</script>

<script>
    (function () {
        var btn = document.getElementById('download-all-docs');
        if (!btn) return;

        btn.addEventListener('click', function () {
            var triggers = document.querySelectorAll('.doc-view-trigger');
            var hint = document.getElementById('download-all-hint');
            // Shown without blocking (no alert()) so it never interrupts the
            // synchronous download loop below — an alert() pauses script
            // execution and can cost the browser's "this came from a real
            // click" activation window, which is exactly what makes the
            // multi-file downloads get blocked in the first place.
            if (hint) hint.classList.remove('hidden');

            triggers.forEach(function (t) {
                var a = document.createElement('a');
                a.href = t.getAttribute('data-url');
                a.download = t.getAttribute('data-name') || '';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            });
        });
    })();
</script>

<?php require __DIR__.'/_layout_footer.php'; ?>
