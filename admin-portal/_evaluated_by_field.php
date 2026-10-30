<?php
if (!defined('PORTAL_BOOTED')) { http_response_code(404); exit; }
/**
 * "Who will evaluate the applications?" choice for a job posting, shared by
 * the Job Postings Manage modal, the Closed Postings Repost modal, and
 * _job_position_form.php. Expects $evaluatedByValue ('evaluator'|'admin'),
 * $evaluatedByIdPrefix, and $assignedEvaluatorIds (int[], default []) in
 * scope. $assignedEvaluatorIds only applies when evaluated_by is 'admin' —
 * it lets the admin optionally bring in specific evaluator(s) to help on an
 * otherwise admin-only (e.g. sensitive) posting, without opening it to every
 * evaluator and without promoting that account to Admin.
 */
$evaluatedByValue = admin_evaluated_by_input($evaluatedByValue ?? null);
$evaluatedByIdPrefix = $evaluatedByIdPrefix ?? 'evaluated-by';
$assignedEvaluatorIds = array_map('intval', $assignedEvaluatorIds ?? []);
$evaluatedByHelp = [
    'evaluator' => 'Evaluator accounts review the applications. Admins can still see everything.',
    'admin' => 'Admins review the applications. Evaluator accounts will not see them, unless you add specific helper(s) below.',
];
$evaluators = admin_active_evaluators();
?>
<fieldset class="sm:col-span-3">
    <legend class="mb-1.5 block text-sm font-bold text-slate-700">Who will evaluate the applications? <span class="text-rose-500">*</span></legend>
    <div class="grid gap-3 sm:grid-cols-2">
        <?php foreach (admin_evaluated_by_options() as $value => $label): ?>
            <label for="<?= admin_e($evaluatedByIdPrefix.'-'.$value) ?>" class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-300 px-4 py-3 transition hover:bg-slate-50 has-[:checked]:border-admin-indigo has-[:checked]:bg-admin-indigoLight">
                <input type="radio" name="evaluated_by" id="<?= admin_e($evaluatedByIdPrefix.'-'.$value) ?>" value="<?= admin_e($value) ?>" <?= $evaluatedByValue === $value ? 'checked' : '' ?> class="evaluated-by-input mt-1 h-4 w-4 accent-admin-indigo" data-evaluated-by-group="<?= admin_e($evaluatedByIdPrefix) ?>">
                <span>
                    <span class="block text-sm font-bold text-slate-800"><?= admin_e($label) ?></span>
                    <span class="block text-xs text-slate-500"><?= admin_e($evaluatedByHelp[$value]) ?></span>
                </span>
            </label>
        <?php endforeach; ?>
    </div>

    <div id="<?= admin_e($evaluatedByIdPrefix) ?>-assigned-list" class="<?= $evaluatedByValue === 'admin' ? '' : 'hidden' ?> mt-3 rounded-xl border border-slate-300 p-3">
        <p class="mb-2 text-xs font-bold text-slate-600">Optionally, let specific evaluator(s) help on this one too:</p>
        <?php if (empty($evaluators)): ?>
            <p class="text-xs text-slate-500">No evaluator accounts exist yet.</p>
        <?php else: ?>
            <div class="grid max-h-40 gap-2 overflow-y-auto sm:grid-cols-2">
                <?php foreach ($evaluators as $ev): ?>
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-slate-50">
                        <input type="checkbox" name="assigned_evaluator_ids[]" value="<?= $ev->id ?>" <?= in_array((int) $ev->id, $assignedEvaluatorIds, true) ? 'checked' : '' ?> class="h-4 w-4 accent-admin-indigo" data-evaluated-by-group="<?= admin_e($evaluatedByIdPrefix) ?>">
                        <span class="text-slate-700"><?= admin_e($ev->name) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php if (isset($errors) && $errors->has('assigned_evaluator_ids')): ?><p class="mt-1 text-xs text-red-600"><?= admin_e($errors->first('assigned_evaluator_ids')) ?></p><?php endif; ?>
</fieldset>

<script>
    /**
     * Shows/hides the helper-evaluator checklist for a given field-group
     * prefix — only relevant under the Admin option. Several of these
     * fieldsets can exist on one page (Manage modal, Repost modal), each
     * identified by its own id prefix.
     */
    window.adminEvaluatedByToggle = window.adminEvaluatedByToggle || function (prefix) {
        var selected = document.querySelector('input[data-evaluated-by-group="' + prefix + '"][name="evaluated_by"]:checked');
        var list = document.getElementById(prefix + '-assigned-list');
        if (!list) return;
        list.classList.toggle('hidden', !selected || selected.value !== 'admin');
    };

    /**
     * Sets the evaluated_by radio and helper-evaluator checkboxes for a
     * field-group prefix to match a position's saved values — used when the
     * Manage/Repost modal's position picker loads an existing posting.
     */
    window.adminSetEvaluatedBy = window.adminSetEvaluatedBy || function (prefix, evaluatedBy, assignedEvaluatorIds) {
        assignedEvaluatorIds = (assignedEvaluatorIds || []).map(String);
        document.querySelectorAll('input[data-evaluated-by-group="' + prefix + '"][name="evaluated_by"]').forEach(function (radio) {
            radio.checked = radio.value === evaluatedBy;
        });
        document.querySelectorAll('input[data-evaluated-by-group="' + prefix + '"][name="assigned_evaluator_ids[]"]').forEach(function (checkbox) {
            checkbox.checked = assignedEvaluatorIds.indexOf(checkbox.value) !== -1;
        });
        window.adminEvaluatedByToggle(prefix);
    };

    document.querySelectorAll('input[data-evaluated-by-group="<?= admin_e($evaluatedByIdPrefix) ?>"][name="evaluated_by"]').forEach(function (radio) {
        radio.addEventListener('change', function () { window.adminEvaluatedByToggle('<?= admin_e($evaluatedByIdPrefix) ?>'); });
    });
</script>
