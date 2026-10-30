<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

admin_require_login();
$user = admin_user();

/*
 * Admin counterpart of evaluator-portal/evaluate.php, for postings whose
 * job_positions.evaluated_by is 'admin' (those are hidden from evaluators).
 * Same checklist, QS and result rules (shared _evaluation.php); unlike the
 * evaluator version, any admin may continue another admin's evaluation.
 */

$applicationId = (int) ($_GET['id'] ?? $_POST['application_id'] ?? 0);
if (!$applicationId) {
    http_response_code(404);
    exit;
}

$returnTo = ['url' => admin_url('applications.php'), 'label' => 'applications'];
$fromParam = '';

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

// Finalized applications (qualified/disqualified) are no longer evaluated.
if (!$job || !in_array($application->status, ['pending', 'evaluated', 'excluded'], true)) {
    flash_set('error', 'This application is already '.ucfirst($application->status).' and can no longer be evaluated.');
    header('Location: '.admin_url('applications.php'));
    exit;
}

$stmt = portal_pdo()->prepare(
    'SELECT e.*, u.name AS owner_name, u.role AS owner_role
     FROM application_evaluations e
     LEFT JOIN users u ON u.id = e.evaluator_id
     WHERE e.application_id = ?'
);
$stmt->execute([$applicationId]);
$existingEvaluation = $stmt->fetch();

// A saved evaluation belongs to whoever saved it — admin or evaluator —
// whichever way the posting is switched later: only its author can edit it.
// Unsaved applications follow the posting: admins evaluate Admin postings.
$hasOwner = $existingEvaluation && $existingEvaluation->evaluator_id && $existingEvaluation->owner_role !== null;
$ownedByMe = $hasOwner && (int) $existingEvaluation->evaluator_id === (int) $user->id;

if ($hasOwner && !$ownedByMe) {
    flash_set('error', $existingEvaluation->owner_role === 'admin'
        ? 'This application is being evaluated by '.$existingEvaluation->owner_name.'. Only the admin who saved the evaluation can edit it.'
        : 'This application was evaluated by evaluator '.$existingEvaluation->owner_name.'. Only they can edit it.');
    header('Location: '.$returnTo['url']);
    exit;
}
if (!$ownedByMe && ($job->evaluated_by ?? 'evaluator') !== 'admin') {
    flash_set('error', 'Evaluator accounts evaluate this posting. Admins can view its applications and make the final decision.');
    header('Location: '.$returnTo['url']);
    exit;
}

// Evaluation only opens once the position's application period has actually
// ended — but that only gates STARTING a fresh evaluation on a still-pending
// application. An application that's already been evaluated or excluded
// must stay editable regardless of the position's current deadline.
$applicationPeriodEnded = !$job->is_open || admin_job_deadline_passed($job->until, $job->until_time);
if ($application->status === 'pending' && !$applicationPeriodEnded) {
    flash_set('error', 'Evaluation opens after the application period for '.$job->title.' ends.');
    header('Location: '.admin_url('applications.php'));
    exit;
}

if (!$existingEvaluation) {
    portal_insert('application_evaluations', [
        'application_id' => $applicationId,
        'result' => EVAL_RESULT_PENDING,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
}

/**
 * True while this row has never actually been saved — evaluator_id is only
 * ever set inside eval_save_checklist() (by Save or Exclude), never by
 * merely opening the page (which creates the row to claim it). A real
 * Save — even a partial one, with Documentary Requirements checked but
 * Qualification Standards not yet filled in — already locks evaluator_id,
 * so other evaluators are blocked from it regardless; this check only
 * decides whether "Back to List" releases a row nobody has saved to yet.
 * (Deliberately not a created_at === updated_at timestamp comparison —
 * that has only 1-second resolution and can misfire when open-then-save
 * happens within the same second.)
 */
function eval_has_unsaved_claim_only(int $applicationId): bool
{
    $stmt = portal_pdo()->prepare('SELECT evaluator_id FROM application_evaluations WHERE application_id = ?');
    $stmt->execute([$applicationId]);
    $evaluation = $stmt->fetch();

    if (!$evaluation) {
        return false;
    }

    return $evaluation->evaluator_id === null;
}

/**
 * Persists the checklist/QS/remarks fields currently on screen and returns
 * the newly computed result — shared by both the "save" and "exclude"
 * actions so Exclude never again silently discards whatever was checked.
 */
function eval_save_checklist(int $applicationId, int $userId): string
{
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

    // Once the Mandatory Requirements are complete, a qualification standard
    // left unmarked counts as Did not Meet the QS — so saving always gives a
    // final result (Qualified or Not Qualified), never Pending Document Review.
    if (eval_is_documentary_complete($mandatorySelected)) {
        $qsEducation ??= false;
        $qsExperience ??= false;
        $qsTraining ??= false;
        $qsEligibility ??= false;
    }
    $remarks = trim($_POST['remarks'] ?? '') ?: null;

    $stmt = portal_pdo()->prepare('SELECT result, documentary_mandatory FROM application_evaluations WHERE application_id = ?');
    $stmt->execute([$applicationId]);
    $prevRow = $stmt->fetch();
    $preUpdateResult = $prevRow->result ?? EVAL_RESULT_PENDING;

    // "Currently excluded" must only stick across re-saves for a *manual*
    // Exclude (made with a complete checklist) — not for the automatic
    // exclusion incomplete mandatory requirements now produce. Otherwise,
    // finishing the checklist on a later save would never be able to move
    // the applicant out of Excluded.
    $prevMandatorySelected = json_decode($prevRow->documentary_mandatory ?? '[]', true) ?: [];
    $wasManuallyExcluded = $preUpdateResult === EVAL_RESULT_EXCLUDED && eval_is_documentary_complete($prevMandatorySelected);

    portal_pdo()->prepare(
        'UPDATE application_evaluations
         SET documentary_mandatory = :dm, documentary_other = :do_, qs_education_met = :qe,
             qs_experience_met = :qx, qs_training_met = :qt, qs_eligibility_met = :ql,
             remarks = :remarks, evaluator_id = COALESCE(evaluator_id, :evaluator_id), updated_at = :updated_at
         WHERE application_id = :aid'
    )->execute([
        'dm' => json_encode($mandatorySelected),
        'do_' => json_encode($otherSelected),
        'qe' => $qsEducation === null ? null : (int) $qsEducation,
        'qx' => $qsExperience === null ? null : (int) $qsExperience,
        'qt' => $qsTraining === null ? null : (int) $qsTraining,
        'ql' => $qsEligibility === null ? null : (int) $qsEligibility,
        'remarks' => $remarks,
        'evaluator_id' => $userId,
        'updated_at' => date('Y-m-d H:i:s'),
        'aid' => $applicationId,
    ]);

    return eval_compute_result(
        $mandatorySelected, $qsEducation, $qsExperience, $qsTraining, $qsEligibility,
        currentlyExcluded: $wasManuallyExcluded
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'cancel') {
        // Leaving without ever saving releases the claim, so this
        // application is free again for any evaluator — including this
        // one — to pick up later. A row that WAS saved (even partially —
        // Documentary Requirements checked, Qualification Standards not
        // yet) is left alone, so an evaluator can split the work across
        // sessions without losing checklist progress.
        if (eval_has_unsaved_claim_only($applicationId)) {
            portal_pdo()->prepare('DELETE FROM application_evaluations WHERE application_id = ?')->execute([$applicationId]);
        }

        header('Location: '.$returnTo['url']);
        exit;
    }

    if ($action === 'exclude') {
        // Persist whatever is currently on screen (checked requirements, QS
        // answers, remarks) first — this used to skip straight to forcing
        // "result", silently discarding every other field the evaluator had
        // filled in.
        eval_save_checklist($applicationId, $user->id);

        // The save above may have just computed "pending document review"
        // (null evaluated_at) if the checklist wasn't complete — Exclude is
        // a deliberate, final call the evaluator can make regardless of
        // checklist completeness, so it must stamp evaluated_at itself.
        // recommended must also be reset to 0 here — otherwise an applicant
        // who was previously Qualified (recommended=1) and then gets
        // Excluded keeps showing as "Recommended" alongside "Excluded".
        portal_pdo()->prepare("UPDATE application_evaluations SET result = ?, evaluated_at = ?, recommended = 0, updated_at = ? WHERE application_id = ?")
            ->execute([EVAL_RESULT_EXCLUDED, date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $applicationId]);

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
        header('Location: '.$returnTo['url']);
        exit;
    }

    if ($action === 'save') {
        $remarks = trim($_POST['remarks'] ?? '') ?: null;
        $result = eval_save_checklist($applicationId, $user->id);

        portal_pdo()->prepare(
            'UPDATE application_evaluations SET evaluated_at = ?, result = ?, recommended = ? WHERE application_id = ?'
        )->execute([
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

        if ($newStatus === 'excluded') {
            flash_set('success', 'Applicant excluded.');
            header('Location: '.$returnTo['url']);
            exit;
        }

        flash_set('success', 'Evaluation saved.');
        header('Location: '.admin_url('application-evaluate.php?id='.$applicationId.$fromParam));
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
$documentsByType = [];
foreach ($documents as $doc) {
    $documentsByType[$doc->type] = $doc;
}

$mandatorySelected = json_decode($evaluation->documentary_mandatory ?? '[]', true) ?: [];
$otherSelected = json_decode($evaluation->documentary_other ?? '[]', true) ?: [];
$qsEducation = eval_bool($evaluation->qs_education_met ?? null);
$qsExperience = eval_bool($evaluation->qs_experience_met ?? null);
$qsTraining = eval_bool($evaluation->qs_training_met ?? null);
$qsEligibility = eval_bool($evaluation->qs_eligibility_met ?? null);
$documentaryComplete = eval_is_documentary_complete($mandatorySelected);

$mandatoryCount = eval_count_selected(eval_mandatory_requirements(), $mandatorySelected);
$otherCount = eval_count_selected(eval_other_requirements(), $otherSelected);

// "Currently excluded" only sticks across re-saves for a manual Exclude made
// with a complete checklist — not for the automatic exclusion an incomplete
// mandatory checklist now produces (see eval_compute_result()).
$wasManuallyExcluded = $evaluation->result === EVAL_RESULT_EXCLUDED && $documentaryComplete;

$computedResult = eval_compute_result($mandatorySelected, $qsEducation, $qsExperience, $qsTraining, $qsEligibility, $wasManuallyExcluded);
$disqualifiedCategories = eval_disqualified_categories($qsEducation, $qsExperience, $qsTraining, $qsEligibility);

// Nothing has actually been saved if this evaluation has never been
// evaluated before (evaluated_at is still null). In that case, the badge
// below is only a live preview of what WOULD happen on save — and if the
// evaluator hasn't touched the checklist at all yet (nothing checked,
// no QS marked), there is nothing to warn about yet, so stay neutral
// rather than defaulting a blank, untouched evaluation straight to a
// scary "Excluded" state. Once they start checking items, or once a save
// has genuinely committed an exclusion, the real computed result shows.
$neverEvaluated = $evaluation->evaluated_at === null;
$hasAnyProgress = $mandatorySelected !== [] || $otherSelected !== []
    || $qsEducation !== null || $qsExperience !== null || $qsTraining !== null || $qsEligibility !== null;

if ($computedResult === EVAL_RESULT_EXCLUDED && $neverEvaluated && !$hasAnyProgress) {
    $computedResult = EVAL_RESULT_PENDING;
}

$missingMandatory = $computedResult === EVAL_RESULT_EXCLUDED ? eval_missing_mandatory_requirements($mandatorySelected) : [];

$resultDescription = eval_result_description($computedResult, $disqualifiedCategories, $missingMandatory);
if ($computedResult === EVAL_RESULT_EXCLUDED && $missingMandatory && $neverEvaluated) {
    $resultDescription = 'Will be automatically excluded if you save now — the Mandatory Requirements checklist below is still incomplete.';
}

$eduRequirementFallback = "Bachelor's Degree in Guidance Counseling or Psychology; or any Bachelor's Degree with atleast eighteen (18) units of courses in Guidance and Psychology; or Any  Bachelor's Degree with a minimum of eighteen (18) units of Behavioral Science courses that shall include 200 hours of supervised practicum or internship experience on guidance and counseling, preferably in a school or community setting";
$eligRequirementFallback = 'Career Service Professional, Second Level Eligibility and RA 1080.';

$colorHex = [
    'success' => ['bg' => '#dcfce7', 'fg' => '#15803d', 'badge' => '#15803d'],
    'danger' => ['bg' => '#fee2e2', 'fg' => '#b91c1c', 'badge' => '#b91c1c'],
    'gray' => ['bg' => '#f1f5f9', 'fg' => '#475569', 'badge' => '#475569'],
];
$resultStyle = $colorHex[eval_result_color($computedResult)];

$pageTitle = ($profile->full_name ?? 'Application').' — Evaluate | Administration';
require __DIR__.'/_layout_head.php';

function admin_qs_button(string $field, ?bool $current, bool $wantTrue): void
{
    $active = $current === $wantTrue;
    $color = $wantTrue ? 'success' : 'danger';
    $classes = $active
        ? ($wantTrue ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-rose-600 text-white border-rose-600')
        : 'bg-white text-slate-500 border-slate-300 hover:bg-slate-50';
    echo '<button type="button" data-qs-btn data-field="'.admin_e($field).'" data-value="'.($wantTrue ? '1' : '0').'" class="qs-btn rounded-lg border px-3 py-1.5 text-xs font-bold transition '.$classes.'">'
        .($wantTrue ? 'Meet the QS' : 'Did not Meet the QS')
        .'</button>';
}
?>

<?php $activePage = 'applications'; require __DIR__.'/_layout_topbar.php'; ?>

<div class="lg:flex lg:flex-1">
    <?php require __DIR__.'/_layout_sidebar.php'; ?>

    <div class="min-w-0 flex-1">
        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">

            <form method="POST" action="<?= admin_url('application-evaluate.php?id='.$applicationId.$fromParam) ?>" class="mb-4 inline-block">
                <?= admin_csrf_field() ?>
                <button type="submit" formnovalidate name="action" value="cancel" class="inline-flex items-center gap-1.5 rounded-lg border-2 border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-600 transition hover:border-admin-indigo hover:text-admin-indigo">&larr; Back to List</button>
            </form>

            <?php if (flash_get('success')): ?>
                <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800"><?= admin_e(flash_get('success')) ?></div>
            <?php endif; ?>

            <form method="POST" action="<?= admin_url('application-evaluate.php?id='.$applicationId.$fromParam) ?>" class="space-y-6">
                <?= admin_csrf_field() ?>
                <input type="hidden" name="action" value="save">

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <?= admin_section_header('Application Info', 'briefcase') ?>
                    <div class="grid gap-4 p-6 sm:grid-cols-2">
                        <div><p class="text-xs font-bold uppercase text-slate-500">Job Position</p><p class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= admin_e($job->title ?? '—') ?></p></div>
                        <div><p class="text-xs font-bold uppercase text-slate-500">Current Status</p><p class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= admin_e(ucfirst($application->status)) ?></p></div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <?= admin_section_header('Control Number', 'hashtag', 'Automatically generated by the system when the applicant submitted this application.') ?>
                    <div class="p-6">
                        <div class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= admin_e($controlNumber ?: 'Not yet assigned') ?></div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <?= admin_section_header('Documentary Requirements', 'clipboard', 'Click submitted requirements. The mandatory requirements unlock the QS Evaluation automatically once completed.') ?>
                    <div class="p-6">
                    <div class="mb-4">
                        <p class="mb-2 text-sm font-bold text-slate-700" id="mandatory-count-label">Mandatory Requirements (<?= $mandatoryCount ?> of <?= eval_mandatory_count() ?>)</p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <?php foreach (eval_mandatory_requirements() as $key => $label): ?>
                                <label class="requirement-card flex items-start gap-2 rounded-lg border border-slate-200 px-3 py-2.5 text-sm transition has-[:checked]:border-emerald-300 has-[:checked]:bg-emerald-50 has-[:checked]:[&_span]:font-bold has-[:checked]:[&_span]:text-green-700">
                                    <input type="checkbox" name="documentary_mandatory[]" value="<?= admin_e($key) ?>" class="mandatory-check mt-0.5 h-4 w-4 accent-emerald-600" <?= in_array($key, $mandatorySelected, true) ? 'checked' : '' ?>>
                                    <span class="text-slate-600"><?= admin_e($label) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <p class="mb-2 text-sm font-bold text-slate-700" id="other-count-label">Other Requirements (<?= $otherCount ?> of <?= eval_other_count() ?>)</p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <?php foreach (eval_other_requirements() as $key => $label): ?>
                                <label class="requirement-card flex items-start gap-2 rounded-lg border border-slate-200 px-3 py-2.5 text-sm transition has-[:checked]:border-emerald-300 has-[:checked]:bg-emerald-50 has-[:checked]:[&_span]:font-bold has-[:checked]:[&_span]:text-green-700">
                                    <input type="checkbox" name="documentary_other[]" value="<?= admin_e($key) ?>" class="other-check mt-0.5 h-4 w-4 accent-emerald-600" <?= in_array($key, $otherSelected, true) ? 'checked' : '' ?>>
                                    <span class="text-slate-600"><?= admin_e($label) ?></span>
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
                    </div>
                </section>

                <section id="qs-section" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" style="<?= $documentaryComplete ? '' : 'display:none;' ?>">
                    <?= admin_section_header('Qualification Standards', 'scale', "Review the applicant's details against each qualification standard, then mark whether each was Meet the QS or Did not Meet the QS.") ?>
                    <div class="grid gap-4 p-6 sm:grid-cols-2">
                        <?php
                        $qsBlocks = [
                            ['field' => 'qs_education_met', 'title' => "Bachelor's Degree", 'applicant' => eval_applicant_bachelors_degree($educations), 'standard' => $job->education_requirement ?: $eduRequirementFallback, 'value' => $qsEducation],
                            ['field' => 'qs_experience_met', 'title' => 'Years of Experience', 'applicant' => eval_applicant_years_of_experience($experiences), 'standard' => ($job->min_experience_years ?? 0).' minimum year(s)', 'value' => $qsExperience],
                            ['field' => 'qs_training_met', 'title' => 'Hours of Training', 'applicant' => eval_applicant_hours_of_training($trainings), 'standard' => ($job->min_training_hours ?? 0).' minimum hour(s)', 'value' => $qsTraining],
                            ['field' => 'qs_eligibility_met', 'title' => 'Eligibility', 'applicant' => eval_applicant_eligibility($eligibilities), 'standard' => $job->eligibility_requirement ?: $eligRequirementFallback, 'value' => $qsEligibility],
                        ];
                        foreach ($qsBlocks as $block): ?>
                            <div class="rounded-xl border border-slate-200 p-4">
                                <p class="mb-2 font-black text-admin-indigoDark"><?= admin_e($block['title']) ?></p>
                                <div class="mb-2 rounded-lg border border-slate-300 p-2 text-sm"><span class="block font-bold">Applicant</span><?= admin_e($block['applicant']) ?></div>
                                <div class="mb-3 rounded-lg border border-slate-300 p-2 text-xs leading-relaxed"><span class="mb-0.5 block font-bold text-sm">Qualification Standard</span><?= admin_e($block['standard']) ?></div>
                                <input type="hidden" name="<?= $block['field'] ?>" value="<?= $block['value'] === null ? '' : ($block['value'] ? '1' : '0') ?>" data-qs-hidden data-field="<?= $block['field'] ?>">
                                <div class="flex gap-2">
                                    <?php admin_qs_button($block['field'], $block['value'], true); ?>
                                    <?php admin_qs_button($block['field'], $block['value'], false); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <?= admin_section_header('Applicant Profile', 'user') ?>
                    <div class="grid gap-4 p-6 sm:grid-cols-2">
                        <?php
                        $profileFields = [
                            'Full Name' => $profile->full_name ?? null, 'Email Address' => $profile->email ?? null,
                            'Phone Number' => $profile->phone ?? null, 'Address' => $profile->address ?? null,
                            'Birth Date' => isset($profile->birth_date) ? (new DateTime($profile->birth_date))->format('M d, Y') : null,
                            'Sex' => $profile->sex ?? null, 'Civil Status' => $profile->civil_status ?? null,
                            'Religion' => $profile->religion ?? null, 'Disability (if any)' => $profile->disability ?? null,
                            'Ethnic Group' => $profile->ethnic_group ?? null,
                        ];
                        foreach ($profileFields as $label => $value): ?>
                            <div class="rounded-lg border border-slate-300 p-2.5"><p class="text-sm font-bold text-slate-700"><?= admin_e($label) ?></p><span class="text-slate-600"><?= admin_e($value ?: '—') ?></span></div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <?= admin_section_header('Evaluation Result', 'flag', 'The status defaults to Pending Document Review until mandatory requirements are complete. Applicants are marked Excluded only when specifically using the Exclude button below.') ?>
                    <div class="p-6">
                        <div id="result-badge-box" class="mb-4 rounded-lg border-2 px-4 py-2" style="background:<?= $resultStyle['bg'] ?>;border-color:<?= $resultStyle['fg'] ?>;">
                            <span id="result-badge-label" class="inline-block rounded-md px-2.5 py-1 text-sm font-bold text-white" style="background:<?= $resultStyle['badge'] ?>;"><?= admin_e(strtoupper(eval_result_label($computedResult))) ?></span>
                            <span id="result-badge-desc" class="ml-2 text-xs font-bold" style="color:#334155;"><?= admin_e($resultDescription) ?></span>
                        </div>
                        <?php if ($missingMandatory): ?>
                            <div id="result-missing-box" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-4">
                                <p class="mb-2 text-xs font-bold uppercase tracking-wide text-rose-700">Missing Mandatory Requirements</p>
                                <ul class="list-inside list-disc space-y-1 text-sm text-rose-700">
                                    <?php foreach ($missingMandatory as $item): ?>
                                        <li><?= admin_e($item) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <label class="mb-1 block text-sm font-bold text-slate-700">Remarks / Notes</label>
                        <textarea name="remarks" rows="4" placeholder="Enter evaluation remarks..." class="mb-4 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= admin_e($evaluation->remarks ?? '') ?></textarea>

                        <div class="flex items-center justify-between">
                            <button type="submit" formaction="<?= admin_url('application-evaluate.php?id='.$applicationId.$fromParam) ?>" formnovalidate data-confirm="This marks the applicant as Excluded from further consideration. You can change this later by re-editing the checklist.&#10;&#10;Exclude this applicant?" name="action" value="exclude" class="rounded-lg border-2 border-rose-600 px-4 py-2 text-sm font-bold text-rose-600 hover:bg-rose-50">
                                Exclude Applicant
                            </button>
                            <button type="submit" id="save-evaluation-btn" data-confirm="Save this evaluation?" name="action" value="save" class="rounded-lg border-2 border-admin-indigo px-4 py-2 text-sm font-bold text-admin-indigo hover:bg-admin-indigoLight">
                                Save Changes
                            </button>
                        </div>
                    </div>
                </section>
            </form>

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
                <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <?= admin_section_header($t['title'], $t['icon']) ?>
                    <div class="p-6">
                    <?php if (empty($t['rows'])): ?>
                        <p class="text-sm text-slate-500">No records submitted.</p>
                    <?php else: ?>
                        <div class="overflow-x-auto"><table class="w-full text-sm">
                            <thead><tr><?php foreach ($t['headers'] as $h): ?><th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold"><?= admin_e($h) ?></th><?php endforeach; ?></tr></thead>
                            <tbody><?php foreach ($t['rows'] as $row): ?><tr><?php foreach ($row as $cell): ?><td class="border-b border-slate-200 px-3 py-2"><?= admin_e((string) $cell) ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody>
                        </table></div>
                    <?php endif; ?>
                    </div>
                </section>
            <?php endforeach; ?>

            <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <?= admin_section_header('Submitted Documents', 'paperclip', null, count($documents) < 2 ? '' : '<button type="button" id="download-all-docs" title="If your browser blocks some files, click the download icon near the address bar and choose Allow — all documents will then download together." class="inline-flex items-center gap-1.5 rounded-lg bg-white/15 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-white/25"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>Download All</button>') ?>
                <div class="p-6">
                <?php if (count($documents) > 1): ?>
                    <p id="download-all-hint" class="mb-3 hidden rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-700">If your browser blocked some of the files, click the download icon near the address bar and choose "Allow" — all documents will then download together.</p>
                <?php endif; ?>
                    <div class="overflow-x-auto"><table class="w-full text-sm">
                        <thead><tr><th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Document Type</th><th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">File</th></tr></thead>
                        <tbody>
                        <?php foreach (DOCUMENT_FIELDS as $field => $label): $doc = $documentsByType[$field] ?? null; ?>
                        <tr>
                            <td class="border-b border-slate-200 px-3 py-2"><span class="pill bg-admin-indigoLight text-admin-indigo"><?= admin_e($label) ?></span></td>
                            <?php if ($doc): ?>
                            <td class="border-b border-slate-200 px-3 py-2"><button type="button" class="doc-view-trigger text-admin-indigo underline" data-url="<?= admin_e(admin_url('document.php?path='.urlencode($doc->file_path))) ?>" data-name="<?= admin_e(basename($doc->file_path)) ?>"><?= admin_e(basename($doc->file_path)) ?></button></td>
                            <?php else: ?>
                            <td class="border-b border-slate-200 px-3 py-2 text-slate-400 italic">No <?= admin_e($label) ?> attached</td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        <?php foreach ($documents as $doc): if (array_key_exists($doc->type, DOCUMENT_FIELDS)) { continue; } ?>
                        <tr>
                            <td class="border-b border-slate-200 px-3 py-2"><span class="pill bg-admin-indigoLight text-admin-indigo"><?= admin_e($doc->type) ?></span></td>
                            <td class="border-b border-slate-200 px-3 py-2"><button type="button" class="doc-view-trigger text-admin-indigo underline" data-url="<?= admin_e(admin_url('document.php?path='.urlencode($doc->file_path))) ?>" data-name="<?= admin_e(basename($doc->file_path)) ?>"><?= admin_e(basename($doc->file_path)) ?></button></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                </div>
            </section>
        </main>
    </div>
</div>

<div id="doc-view-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-2 sm:p-4">
    <div class="flex h-[97vh] w-full max-w-[98vw] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <h3 id="doc-view-modal-title" class="truncate text-lg font-black text-admin-indigoDark">Document</h3>
            <div class="flex items-center gap-4">
                <a id="doc-view-modal-newtab" href="#" target="_blank" rel="noopener" class="text-sm font-bold text-admin-indigo hover:underline">Open in new tab</a>
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
        if (modal) {
            var openModal = function (url, name) {
                titleEl.textContent = name || 'Document';
                newTabLink.href = url;
                frame.src = url;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };
            var closeModal = function () {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                frame.src = 'about:blank';
            };
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
        }
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
    // Only a manual Exclude made with a complete checklist sticks across
    // re-saves — matches eval_compute_result()'s PHP-side rule.
    var WAS_MANUALLY_EXCLUDED = <?= $wasManuallyExcluded ? 'true' : 'false' ?>;
    var NEVER_EVALUATED = <?= $neverEvaluated ? 'true' : 'false' ?>;
    var MANDATORY_LABELS = <?= json_encode(eval_mandatory_requirements()) ?>;

    function qsBool(field) {
        var v = document.querySelector('[data-qs-hidden][data-field="' + field + '"]').value;
        if (v === '1') return true;
        if (v === '0') return false;
        return null;
    }

    function recomputeResult() {
        var e = qsBool('qs_education_met'), x = qsBool('qs_experience_met'), t = qsBool('qs_training_met'), l = qsBool('qs_eligibility_met');

        var missing = [];
        document.querySelectorAll('.mandatory-check').forEach(function (el) {
            if (!el.checked) missing.push(MANDATORY_LABELS[el.value]);
        });
        var docComplete = missing.length === 0;
        var otherChecked = countChecked('.other-check') > 0;
        // A never-saved evaluation that the evaluator hasn't touched at all
        // yet (nothing checked, no QS marked) has nothing to warn about —
        // don't default a blank page straight to "Excluded".
        var hasAnyProgress = missing.length < mandatoryTotal || otherChecked || e !== null || x !== null || t !== null || l !== null;

        var result, color, label, desc;
        var disqualified = [];
        if (e === false) disqualified.push("Bachelor's Degree");
        if (x === false) disqualified.push('Years of Experience');
        if (t === false) disqualified.push('Hours of Training');
        if (l === false) disqualified.push('Eligibility');

        if (!docComplete && NEVER_EVALUATED && !hasAnyProgress) {
            result = 'pending'; color = 'gray'; label = 'Pending Document Review';
            desc = 'The status defaults to Pending Document Review until mandatory requirements are complete. Applicants are marked Qualified when all qualification standards are marked Meet the QS. Applicants can be marked Excluded only when specifically using the Exclude button below.';
            missing = [];
        } else if (!docComplete) {
            result = 'excluded'; color = 'danger'; label = 'Excluded';
            desc = 'Will be automatically excluded if you save now — the Mandatory Requirements checklist below is still incomplete.';
        } else if (e === true && x === true && t === true && l === true) {
            result = 'qualified'; color = 'success'; label = 'Qualified';
            desc = 'Documents are complete and all qualification standards are marked Meet the QS.';
            missing = [];
        } else if (WAS_MANUALLY_EXCLUDED) {
            result = 'excluded'; color = 'danger'; label = 'Excluded';
            desc = 'This applicant was excluded during evaluation.';
            missing = [];
        } else {
            // Documents complete: a standard left unmarked counts as Did not Meet the QS on save.
            var notMet = [];
            if (e !== true) notMet.push("Bachelor's Degree");
            if (x !== true) notMet.push('Years of Experience');
            if (t !== true) notMet.push('Hours of Training');
            if (l !== true) notMet.push('Eligibility');
            var anyUnmarked = e === null || x === null || t === null || l === null;
            result = 'not_qualified'; color = 'danger'; label = 'Not Qualified';
            desc = 'Did not Meet the QS: ' + notMet.join(', ') + '.'
                + (anyUnmarked ? ' Standards not yet marked count as Did not Meet the QS when saved.' : '');
            missing = [];
        }

        var s = RESULT_STYLES[color];
        var box = document.getElementById('result-badge-box');
        box.style.background = s.bg;
        box.style.borderColor = s.fg;
        document.getElementById('result-badge-label').style.background = s.badge;
        document.getElementById('result-badge-label').textContent = label.toUpperCase();
        document.getElementById('result-badge-desc').textContent = desc;

        var missingBox = document.getElementById('result-missing-box');
        if (missing.length) {
            if (!missingBox) {
                missingBox = document.createElement('div');
                missingBox.id = 'result-missing-box';
                missingBox.className = 'mb-4 rounded-lg border border-rose-200 bg-rose-50 p-4';
                missingBox.innerHTML = '<p class="mb-2 text-xs font-bold uppercase tracking-wide text-rose-700">Missing Mandatory Requirements</p><ul class="list-inside list-disc space-y-1 text-sm text-rose-700"></ul>';
                box.insertAdjacentElement('afterend', missingBox);
            }
            var list = missingBox.querySelector('ul');
            list.innerHTML = '';
            missing.forEach(function (label) {
                var li = document.createElement('li');
                li.textContent = label;
                list.appendChild(li);
            });
            missingBox.style.display = '';
        } else if (missingBox) {
            missingBox.style.display = 'none';
        }
        updateSaveConfirm();
    }

    // The note shown before saving (like Exclude's), kept in sync with what the
    // save will record — above all, that an incomplete Mandatory Requirements
    // checklist marks the applicant Excluded.
    function updateSaveConfirm() {
        var saveBtn = document.getElementById('save-evaluation-btn');
        if (!saveBtn) return;

        var unchecked = mandatoryTotal - countChecked('.mandatory-check');
        var e = qsBool('qs_education_met'), x = qsBool('qs_experience_met'), t = qsBool('qs_training_met'), l = qsBool('qs_eligibility_met');
        var failed = [];
        // Not met = marked Did not Meet, or left unmarked (saved as Did not Meet).
        if (e !== true) failed.push("Bachelor's Degree");
        if (x !== true) failed.push('Years of Experience');
        if (t !== true) failed.push('Hours of Training');
        if (l !== true) failed.push('Eligibility');
        var unmarkedCount = [e, x, t, l].filter(function (v) { return v === null; }).length;

        var note;
        if (unchecked > 0) {
            note = 'Saving now will mark this applicant EXCLUDED because ' + unchecked + ' of ' + mandatoryTotal
                + ' Mandatory Requirements are still unchecked.\n\nCheck every submitted mandatory requirement first if this is not intended.\n\nSave anyway?';
        } else if (e === true && x === true && t === true && l === true) {
            note = 'This saves the evaluation as QUALIFIED: documents are complete and all qualification standards are marked Meet the QS.\n\nSave changes?';
        } else if (WAS_MANUALLY_EXCLUDED) {
            note = 'This applicant stays EXCLUDED (excluded earlier with the Exclude button).\n\nSave changes?';
        } else {
            note = 'This saves the evaluation as NOT QUALIFIED. Did not Meet the QS: ' + failed.join(', ') + '.'
                + (unmarkedCount ? '\n\n' + unmarkedCount + ' qualification standard(s) are not marked yet and will be saved as Did not Meet the QS.' : '')
                + '\n\nSave changes?';
        }
        saveBtn.setAttribute('data-confirm', note);
    }

    updateSaveConfirm();
})();
</script>

<?php require __DIR__.'/_layout_footer.php'; ?>
