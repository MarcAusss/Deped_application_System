<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

evaluator_require_login();
$user = evaluator_user();

$applicationId = (int) ($_GET['id'] ?? $_POST['application_id'] ?? 0);
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

// Lazily create the evaluation row, tagging the current evaluator only on
// first creation — mirrors mutateFormDataBeforeFill()'s firstOrCreate().
$stmt = portal_pdo()->prepare('SELECT id FROM application_evaluations WHERE application_id = ?');
$stmt->execute([$applicationId]);
if (!$stmt->fetch()) {
    portal_insert('application_evaluations', [
        'application_id' => $applicationId,
        'evaluator_id' => $user->id,
        'result' => EVAL_RESULT_PENDING,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'exclude') {
        portal_pdo()->prepare("UPDATE application_evaluations SET result = ?, updated_at = ? WHERE application_id = ?")
            ->execute([EVAL_RESULT_EXCLUDED, date('Y-m-d H:i:s'), $applicationId]);

        if (in_array($application->status, ['pending', 'evaluated'], true)) {
            $stmt = portal_pdo()->prepare('SELECT remarks FROM application_evaluations WHERE application_id = ?');
            $stmt->execute([$applicationId]);
            $currentRemarks = $stmt->fetch()->remarks ?? null;

            portal_pdo()->prepare('UPDATE applications SET status = ?, updated_at = ? WHERE id = ?')
                ->execute(['excluded', date('Y-m-d H:i:s'), $applicationId]);

            portal_insert('application_status_logs', [
                'application_id' => $applicationId,
                'status' => 'excluded',
                'remarks' => $currentRemarks,
                'changed_by' => $user->id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        flash_set('success', 'Applicant excluded.');
        header('Location: '.evaluator_url('evaluate.php?id='.$applicationId));
        exit;
    }

    if ($action === 'save') {
        $mandatorySelected = $_POST['documentary_mandatory'] ?? [];
        $otherSelected = $_POST['documentary_other'] ?? [];
        $mandatorySelected = array_values(array_intersect($mandatorySelected, array_keys(eval_mandatory_requirements())));
        $otherSelected = array_values(array_intersect($otherSelected, array_keys(eval_other_requirements())));

        $qsValue = fn (string $field) => match ($_POST[$field] ?? '') {
            '1' => true,
            '0' => false,
            default => null,
        };
        $qsEducation = $qsValue('qs_education_met');
        $qsExperience = $qsValue('qs_experience_met');
        $qsTraining = $qsValue('qs_training_met');
        $qsEligibility = $qsValue('qs_eligibility_met');
        $remarks = trim($_POST['remarks'] ?? '') ?: null;

        // Read the pre-update result for the currentlyExcluded check — result
        // is never itself a form field, matches afterSave()'s behavior.
        $stmt = portal_pdo()->prepare('SELECT result FROM application_evaluations WHERE application_id = ?');
        $stmt->execute([$applicationId]);
        $preUpdateResult = $stmt->fetch()->result ?? EVAL_RESULT_PENDING;

        portal_pdo()->prepare(
            'UPDATE application_evaluations
             SET documentary_mandatory = :dm, documentary_other = :do_, qs_education_met = :qe,
                 qs_experience_met = :qx, qs_training_met = :qt, qs_eligibility_met = :ql,
                 remarks = :remarks, updated_at = :updated_at
             WHERE application_id = :aid'
        )->execute([
            'dm' => json_encode($mandatorySelected),
            'do_' => json_encode($otherSelected),
            'qe' => $qsEducation === null ? null : (int) $qsEducation,
            'qx' => $qsExperience === null ? null : (int) $qsExperience,
            'qt' => $qsTraining === null ? null : (int) $qsTraining,
            'ql' => $qsEligibility === null ? null : (int) $qsEligibility,
            'remarks' => $remarks,
            'updated_at' => date('Y-m-d H:i:s'),
            'aid' => $applicationId,
        ]);

        $result = eval_compute_result(
            $mandatorySelected, $qsEducation, $qsExperience, $qsTraining, $qsEligibility,
            currentlyExcluded: $preUpdateResult === EVAL_RESULT_EXCLUDED
        );

        portal_pdo()->prepare(
            'UPDATE application_evaluations SET evaluator_id = ?, evaluated_at = ?, result = ?, recommended = ? WHERE application_id = ?'
        )->execute([
            $user->id,
            $result === EVAL_RESULT_PENDING ? null : date('Y-m-d H:i:s'),
            $result,
            $result === EVAL_RESULT_QUALIFIED ? 1 : 0,
            $applicationId,
        ]);

        $newStatus = match ($result) {
            EVAL_RESULT_QUALIFIED, EVAL_RESULT_NOT_QUALIFIED => 'evaluated',
            EVAL_RESULT_EXCLUDED => 'excluded',
            default => 'pending',
        };

        if ($application->status !== $newStatus && in_array($application->status, ['pending', 'evaluated', 'excluded'], true)) {
            portal_pdo()->prepare('UPDATE applications SET status = ?, updated_at = ? WHERE id = ?')
                ->execute([$newStatus, date('Y-m-d H:i:s'), $applicationId]);

            portal_insert('application_status_logs', [
                'application_id' => $applicationId,
                'status' => $newStatus,
                'remarks' => $remarks,
                'changed_by' => $user->id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        flash_set('success', 'Evaluation saved.');
        header('Location: '.evaluator_url('evaluate.php?id='.$applicationId));
        exit;
    }

    http_response_code(400);
    exit;
}

// ---------------------------------------------------------------------
// GET: render
// ---------------------------------------------------------------------

$stmt = portal_pdo()->prepare('SELECT * FROM application_evaluations WHERE application_id = ?');
$stmt->execute([$applicationId]);
$evaluation = $stmt->fetch();

$stmt = portal_pdo()->prepare('SELECT * FROM applicant_profiles WHERE application_id = ?');
$stmt->execute([$applicationId]);
$profile = $stmt->fetch();

$stmt = portal_pdo()->prepare('SELECT control_number FROM application_control_numbers WHERE application_id = ?');
$stmt->execute([$applicationId]);
$controlNumber = $stmt->fetch()->control_number ?? null;

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

$mandatorySelected = json_decode($evaluation->documentary_mandatory ?? '[]', true) ?: [];
$otherSelected = json_decode($evaluation->documentary_other ?? '[]', true) ?: [];
$qsEducation = eval_bool($evaluation->qs_education_met ?? null);
$qsExperience = eval_bool($evaluation->qs_experience_met ?? null);
$qsTraining = eval_bool($evaluation->qs_training_met ?? null);
$qsEligibility = eval_bool($evaluation->qs_eligibility_met ?? null);
$documentaryComplete = eval_is_documentary_complete($mandatorySelected);

$mandatoryCount = eval_count_selected(eval_mandatory_requirements(), $mandatorySelected);
$otherCount = eval_count_selected(eval_other_requirements(), $otherSelected);

$computedResult = eval_compute_result($mandatorySelected, $qsEducation, $qsExperience, $qsTraining, $qsEligibility, $evaluation->result === EVAL_RESULT_EXCLUDED);
$disqualifiedCategories = eval_disqualified_categories($qsEducation, $qsExperience, $qsTraining, $qsEligibility);

$eduRequirementFallback = "Bachelor's Degree in Guidance Counseling or Psychology; or any Bachelor's Degree with atleast eighteen (18) units of courses in Guidance and Psychology; or Any  Bachelor's Degree with a minimum of eighteen (18) units of Behavioral Science courses that shall include 200 hours of supervised practicum or internship experience on guidance and counseling, preferably in a school or community setting";
$eligRequirementFallback = 'Career Service Professional, Second Level Eligibility and RA 1080.';

$colorHex = [
    'success' => ['bg' => '#dcfce7', 'fg' => '#15803d', 'badge' => '#15803d'],
    'danger' => ['bg' => '#fee2e2', 'fg' => '#b91c1c', 'badge' => '#b91c1c'],
    'gray' => ['bg' => '#f1f5f9', 'fg' => '#475569', 'badge' => '#475569'],
];
$resultStyle = $colorHex[eval_result_color($computedResult)];

$pageTitle = ($profile->full_name ?? 'Application').' — Evaluate | Evaluation Desk';
require __DIR__.'/_layout_head.php';

function qs_button(string $field, ?bool $current, bool $wantTrue): void
{
    $active = $current === $wantTrue;
    $color = $wantTrue ? 'success' : 'danger';
    $classes = $active
        ? ($wantTrue ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-rose-600 text-white border-rose-600')
        : 'bg-white text-slate-500 border-slate-300 hover:bg-slate-50';
    echo '<button type="button" data-qs-btn data-field="'.evaluator_e($field).'" data-value="'.($wantTrue ? '1' : '0').'" class="qs-btn rounded-lg border px-3 py-1.5 text-xs font-bold transition '.$classes.'">'
        .($wantTrue ? 'Meet the QS' : 'Did not Meet the QS')
        .'</button>';
}
?>

<?php $activePage = 'applications'; require __DIR__.'/_layout_topbar.php'; ?>

<div class="lg:flex lg:flex-1">
    <?php require __DIR__.'/_layout_sidebar.php'; ?>

    <div class="min-w-0 flex-1">
        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">

            <a href="<?= evaluator_url('applications.php') ?>" class="mb-4 inline-flex items-center gap-1.5 text-sm font-bold text-slate-500 hover:text-evaluator-indigo">&larr; Back to Applications</a>

            <?php if (flash_get('success')): ?>
                <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800"><?= evaluator_e(flash_get('success')) ?></div>
            <?php endif; ?>

            <form method="POST" action="<?= evaluator_url('evaluate.php?id='.$applicationId) ?>" class="space-y-6">
                <?= evaluator_csrf_field() ?>
                <input type="hidden" name="action" value="save">

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-lg font-black text-evaluator-indigoDark">Application Info</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><p class="text-xs font-bold uppercase text-slate-500">Job Position</p><p class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= evaluator_e($job->title ?? '—') ?></p></div>
                        <div><p class="text-xs font-bold uppercase text-slate-500">Current Status</p><p class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= evaluator_e(ucfirst($application->status)) ?></p></div>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-1 text-lg font-black text-evaluator-indigoDark">Control Number</h3>
                    <p class="mb-3 text-xs text-slate-500">Automatically generated by the system when the applicant submitted this application.</p>
                    <div class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= evaluator_e($controlNumber ?: 'Not yet assigned') ?></div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-1 text-lg font-black text-evaluator-indigoDark">Documentary Requirements</h3>
                    <p class="mb-4 text-xs text-slate-500">Click submitted requirements. The mandatory requirements unlock the QS Evaluation automatically once completed.</p>

                    <div class="mb-4 rounded-xl border border-slate-200 p-4">
                        <p class="mb-2 text-sm font-bold text-slate-700" id="mandatory-count-label">Mandatory Requirements (<?= $mandatoryCount ?> of <?= eval_mandatory_count() ?>)</p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <?php foreach (eval_mandatory_requirements() as $key => $label): ?>
                                <label class="flex items-start gap-2 text-sm">
                                    <input type="checkbox" name="documentary_mandatory[]" value="<?= evaluator_e($key) ?>" class="mandatory-check mt-1" <?= in_array($key, $mandatorySelected, true) ? 'checked' : '' ?>>
                                    <span><?= evaluator_e($label) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-4 rounded-xl border border-slate-200 p-4">
                        <p class="mb-2 text-sm font-bold text-slate-700" id="other-count-label">Other Requirements (<?= $otherCount ?> of <?= eval_other_count() ?>)</p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <?php foreach (eval_other_requirements() as $key => $label): ?>
                                <label class="flex items-start gap-2 text-sm">
                                    <input type="checkbox" name="documentary_other[]" value="<?= evaluator_e($key) ?>" class="other-check mt-1" <?= in_array($key, $otherSelected, true) ? 'checked' : '' ?>>
                                    <span><?= evaluator_e($label) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <p id="documentary-summary" class="text-sm">
                        <?php if ($mandatoryCount === eval_mandatory_count()): ?>
                            <span class="font-bold text-emerald-700">All <?= $mandatoryCount ?> Mandatory Requirements complete (<?= $mandatoryCount + $otherCount ?> of <?= eval_mandatory_count() + eval_other_count() ?> total requirements submitted).</span> The QS Evaluation is unlocked below.
                        <?php else: ?>
                            <?= $mandatoryCount ?> of <?= eval_mandatory_count() ?> Mandatory Requirements complete. Complete all mandatory requirements to unlock the QS Evaluation.
                        <?php endif; ?>
                    </p>
                </section>

                <section id="qs-section" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" style="<?= $documentaryComplete ? '' : 'display:none;' ?>">
                    <h3 class="mb-1 text-lg font-black text-evaluator-indigoDark">Qualification Standards</h3>
                    <p class="mb-4 text-xs text-slate-500">Review the applicant's details against each qualification standard, then mark whether each was Meet the QS or Did not Meet the QS.</p>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <?php
                        $qsBlocks = [
                            ['field' => 'qs_education_met', 'title' => "Bachelor's Degree", 'applicant' => eval_applicant_bachelors_degree($educations), 'standard' => $job->education_requirement ?: $eduRequirementFallback, 'value' => $qsEducation],
                            ['field' => 'qs_experience_met', 'title' => 'Years of Experience', 'applicant' => eval_applicant_years_of_experience($experiences), 'standard' => ($job->min_experience_years ?? 0).' minimum year(s)', 'value' => $qsExperience],
                            ['field' => 'qs_training_met', 'title' => 'Hours of Training', 'applicant' => eval_applicant_hours_of_training($trainings), 'standard' => ($job->min_training_hours ?? 0).' minimum hour(s)', 'value' => $qsTraining],
                            ['field' => 'qs_eligibility_met', 'title' => 'Eligibility', 'applicant' => eval_applicant_eligibility($eligibilities), 'standard' => $job->eligibility_requirement ?: $eligRequirementFallback, 'value' => $qsEligibility],
                        ];
                        foreach ($qsBlocks as $block): ?>
                            <div class="rounded-xl border border-slate-200 p-4">
                                <p class="mb-2 font-black text-evaluator-indigoDark"><?= evaluator_e($block['title']) ?></p>
                                <div class="mb-2 rounded-lg border border-slate-300 p-2 text-sm"><span class="block font-bold">Applicant</span><?= evaluator_e($block['applicant']) ?></div>
                                <div class="mb-3 rounded-lg border border-slate-300 p-2 text-xs leading-relaxed"><span class="mb-0.5 block font-bold text-sm">Qualification Standard</span><?= evaluator_e($block['standard']) ?></div>
                                <input type="hidden" name="<?= $block['field'] ?>" value="<?= $block['value'] === null ? '' : ($block['value'] ? '1' : '0') ?>" data-qs-hidden data-field="<?= $block['field'] ?>">
                                <div class="flex gap-2">
                                    <?php qs_button($block['field'], $block['value'], true); ?>
                                    <?php qs_button($block['field'], $block['value'], false); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-lg font-black text-evaluator-indigoDark">Applicant Profile</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
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

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-lg font-black text-evaluator-indigoDark">Evaluation Result</h3>
                    <p class="mb-3 text-xs text-slate-500">The status defaults to Pending Document Review until mandatory requirements are complete. Applicants are marked Excluded only when specifically using the Exclude button below.</p>

                    <div id="result-badge-box" class="mb-4 rounded-lg border-2 px-4 py-2" style="background:<?= $resultStyle['bg'] ?>;border-color:<?= $resultStyle['fg'] ?>;">
                        <span id="result-badge-label" class="inline-block rounded-md px-2.5 py-1 text-sm font-bold text-white" style="background:<?= $resultStyle['badge'] ?>;"><?= evaluator_e(strtoupper(eval_result_label($computedResult))) ?></span>
                        <span id="result-badge-desc" class="ml-2 text-xs font-bold" style="color:#1e3a8a;"><?= evaluator_e(eval_result_description($computedResult, $disqualifiedCategories)) ?></span>
                    </div>

                    <label class="mb-1 block text-sm font-bold text-slate-700">Remarks / Notes</label>
                    <textarea name="remarks" rows="4" placeholder="Enter evaluation remarks..." class="mb-4 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= evaluator_e($evaluation->remarks ?? '') ?></textarea>

                    <div class="flex items-center justify-between">
                        <button type="submit" formaction="<?= evaluator_url('evaluate.php?id='.$applicationId) ?>" formnovalidate onclick="return confirm('This marks the applicant as Excluded from further consideration. You can change this later by re-editing the checklist.\n\nExclude this applicant?');" name="action" value="exclude" class="rounded-lg border-2 border-rose-600 px-4 py-2 text-sm font-bold text-rose-600 hover:bg-rose-50">
                            Exclude Applicant
                        </button>
                        <button type="submit" name="action" value="save" class="rounded-lg border-2 border-evaluator-indigo px-4 py-2 text-sm font-bold text-evaluator-indigo hover:bg-evaluator-indigoLight">
                            Save Changes
                        </button>
                    </div>
                </section>
            </form>

            <?php
            $readonlyTables = [
                ['title' => 'Educational Background', 'headers' => ['Level', 'School / Institution', 'Degree / Course', 'Year Graduated'], 'rows' => array_map(fn ($e) => [
                    ($e->level === "Other's" && $e->level_specify) ? $e->level.' - '.$e->level_specify : $e->level,
                    $e->school ?: '—', $e->degree ?: '—', $e->year_graduated ?: '—',
                ], $educations)],
                ['title' => 'Work Experience', 'headers' => ['Job Title', 'Company', 'First Day', 'Last Day', 'Details'], 'rows' => array_map(fn ($e) => [
                    $e->title ?: '—', $e->company ?: '—', $e->first_day ?: 'Not provided', $e->last_day ?: 'Not provided', $e->details ?: 'Not provided',
                ], $experiences)],
                ['title' => 'Trainings & Seminars', 'headers' => ['Title', 'Hours', 'Start', 'End'], 'rows' => array_map(fn ($t) => [
                    $t->title ?: '—', ($t->hours ?: '0').' hrs',
                    $t->training_date ? date('F Y', strtotime($t->training_date)) : 'Not provided',
                    $t->training_end_date ? date('F Y', strtotime($t->training_end_date)) : 'Not provided',
                ], $trainings)],
                ['title' => 'Eligibilities / Licenses', 'headers' => ['License / Eligibility', 'Rating', 'Date Issued', 'Valid Until'], 'rows' => array_map(fn ($el) => [
                    (in_array($el->license_name, ['RA1080', "Other's"], true) && $el->license_specify) ? $el->license_name.' - '.$el->license_specify : $el->license_name,
                    $el->rating ?: '—',
                    $el->date_issued ? date('M d, Y', strtotime($el->date_issued)) : '—',
                    eval_bool($el->never_expires) ? 'Never Expires' : ($el->valid_until ? date('M d, Y', strtotime($el->valid_until)) : '—'),
                ], $eligibilities)],
            ];
            foreach ($readonlyTables as $t): ?>
                <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-lg font-black text-evaluator-indigoDark"><?= evaluator_e($t['title']) ?></h3>
                    <?php if (empty($t['rows'])): ?>
                        <p class="text-sm text-slate-500">No records submitted.</p>
                    <?php else: ?>
                        <div class="overflow-x-auto"><table class="w-full text-sm">
                            <thead><tr><?php foreach ($t['headers'] as $h): ?><th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold"><?= evaluator_e($h) ?></th><?php endforeach; ?></tr></thead>
                            <tbody><?php foreach ($t['rows'] as $row): ?><tr><?php foreach ($row as $cell): ?><td class="border-b border-slate-200 px-3 py-2"><?= evaluator_e((string) $cell) ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody>
                        </table></div>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>

            <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-black text-evaluator-indigoDark">Submitted Documents</h3>
                <?php if (empty($documents)): ?>
                    <p class="text-sm text-slate-500">No records submitted.</p>
                <?php else: ?>
                    <div class="overflow-x-auto"><table class="w-full text-sm">
                        <thead><tr><th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Document Type</th><th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">File</th></tr></thead>
                        <tbody><?php foreach ($documents as $doc): ?><tr>
                            <td class="border-b border-slate-200 px-3 py-2"><span class="pill bg-evaluator-indigoLight text-evaluator-indigo"><?= evaluator_e($doc->type) ?></span></td>
                            <td class="border-b border-slate-200 px-3 py-2"><a href="<?= evaluator_url('document.php?path='.urlencode($doc->file_path)) ?>" target="_blank" rel="noopener" class="text-evaluator-indigo underline"><?= evaluator_e(basename($doc->file_path)) ?></a></td>
                        </tr><?php endforeach; ?></tbody>
                    </table></div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>

<script>
(function () {
    var mandatoryTotal = <?= eval_mandatory_count() ?>;
    var otherTotal = <?= eval_other_count() ?>;

    function countChecked(selector) {
        return document.querySelectorAll(selector + ':checked').length;
    }

    function refreshCounts() {
        var m = countChecked('.mandatory-check');
        var o = countChecked('.other-check');
        document.getElementById('mandatory-count-label').textContent = 'Mandatory Requirements (' + m + ' of ' + mandatoryTotal + ')';
        document.getElementById('other-count-label').textContent = 'Other Requirements (' + o + ' of ' + otherTotal + ')';

        var summary = document.getElementById('documentary-summary');
        if (m === mandatoryTotal) {
            summary.innerHTML = '<span class="font-bold text-emerald-700">All ' + m + ' Mandatory Requirements complete (' + (m + o) + ' of ' + (mandatoryTotal + otherTotal) + ' total requirements submitted).</span> The QS Evaluation is unlocked below.';
        } else {
            summary.textContent = m + ' of ' + mandatoryTotal + ' Mandatory Requirements complete. Complete all mandatory requirements to unlock the QS Evaluation.';
        }

        document.getElementById('qs-section').style.display = (m === mandatoryTotal) ? '' : 'none';
        recomputeResult();
    }

    document.querySelectorAll('.mandatory-check, .other-check').forEach(function (el) {
        el.addEventListener('change', refreshCounts);
    });

    document.querySelectorAll('[data-qs-btn]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var field = btn.dataset.field;
            var value = btn.dataset.value;
            var hidden = document.querySelector('[data-qs-hidden][data-field="' + field + '"]');
            hidden.value = value;

            document.querySelectorAll('[data-qs-btn][data-field="' + field + '"]').forEach(function (b) {
                var isThis = b === btn;
                var isYes = b.dataset.value === '1';
                b.className = 'qs-btn rounded-lg border px-3 py-1.5 text-xs font-bold transition ' + (isThis
                    ? (isYes ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-rose-600 text-white border-rose-600')
                    : 'bg-white text-slate-500 border-slate-300 hover:bg-slate-50');
            });

            recomputeResult();
        });
    });

    var RESULT_STYLES = {
        success: { bg: '#dcfce7', fg: '#15803d', badge: '#15803d' },
        danger: { bg: '#fee2e2', fg: '#b91c1c', badge: '#b91c1c' },
        gray: { bg: '#f1f5f9', fg: '#475569', badge: '#475569' }
    };
    var CURRENTLY_EXCLUDED = <?= $evaluation->result === EVAL_RESULT_EXCLUDED ? 'true' : 'false' ?>;

    function qsBool(field) {
        var v = document.querySelector('[data-qs-hidden][data-field="' + field + '"]').value;
        if (v === '1') return true;
        if (v === '0') return false;
        return null;
    }

    function recomputeResult() {
        var m = countChecked('.mandatory-check');
        var docComplete = m === mandatoryTotal;
        var e = qsBool('qs_education_met'), x = qsBool('qs_experience_met'), t = qsBool('qs_training_met'), l = qsBool('qs_eligibility_met');

        var result, color, label, desc;
        var disqualified = [];
        if (e === false) disqualified.push("Bachelor's Degree");
        if (x === false) disqualified.push('Years of Experience');
        if (t === false) disqualified.push('Hours of Training');
        if (l === false) disqualified.push('Eligibility');

        if (!docComplete) {
            result = 'pending'; color = 'gray'; label = 'Pending Document Review';
            desc = 'The status defaults to Pending Document Review until mandatory requirements are complete. Applicants are marked Qualified when all qualification standards are marked Meet the QS. Applicants can be marked Excluded only when specifically using the Exclude button below.';
        } else if (e === true && x === true && t === true && l === true) {
            result = 'qualified'; color = 'success'; label = 'Qualified';
            desc = 'Documents are complete and all qualification standards are marked Meet the QS.';
        } else if (CURRENTLY_EXCLUDED) {
            result = 'excluded'; color = 'danger'; label = 'Excluded';
            desc = 'This applicant does Not Meet Qualification Standards.';
        } else if (e === false || x === false || t === false || l === false) {
            result = 'not_qualified'; color = 'danger'; label = 'Not Qualified';
            desc = disqualified.length ? 'Did not Meet the QS: ' + disqualified.join(', ') + '.' : 'At least one qualification standard is marked Did not Meet the QS.';
        } else {
            result = 'pending'; color = 'gray'; label = 'Pending Document Review';
            desc = 'The status defaults to Pending Document Review until mandatory requirements are complete. Applicants are marked Qualified when all qualification standards are marked Meet the QS. Applicants can be marked Excluded only when specifically using the Exclude button below.';
        }

        var s = RESULT_STYLES[color];
        var box = document.getElementById('result-badge-box');
        box.style.background = s.bg;
        box.style.borderColor = s.fg;
        document.getElementById('result-badge-label').style.background = s.badge;
        document.getElementById('result-badge-label').textContent = label.toUpperCase();
        document.getElementById('result-badge-desc').textContent = desc;
    }
})();
</script>

<?php require __DIR__.'/_layout_footer.php'; ?>
