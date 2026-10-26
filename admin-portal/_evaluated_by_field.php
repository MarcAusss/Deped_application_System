<?php
if (!defined('PORTAL_BOOTED')) { http_response_code(404); exit; }
/**
 * "Who will evaluate the applications?" choice for a job posting, shared by
 * the Job Postings Manage modal and _job_position_form.php. Expects
 * $evaluatedByValue ('evaluator'|'admin') and $evaluatedByIdPrefix in scope.
 */
$evaluatedByValue = admin_evaluated_by_input($evaluatedByValue ?? null);
$evaluatedByIdPrefix = $evaluatedByIdPrefix ?? 'evaluated-by';
$evaluatedByHelp = [
    'evaluator' => 'Evaluator accounts review the applications. Admins can still see everything.',
    'admin' => 'Admins review the applications. Evaluator accounts will not see them.',
];
?>
<fieldset class="sm:col-span-3">
    <legend class="mb-1.5 block text-sm font-bold text-slate-700">Who will evaluate the applications? <span class="text-rose-500">*</span></legend>
    <div class="grid gap-3 sm:grid-cols-2">
        <?php foreach (admin_evaluated_by_options() as $value => $label): ?>
            <label for="<?= admin_e($evaluatedByIdPrefix.'-'.$value) ?>" class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-300 px-4 py-3 transition hover:bg-slate-50 has-[:checked]:border-admin-indigo has-[:checked]:bg-admin-indigoLight">
                <input type="radio" name="evaluated_by" id="<?= admin_e($evaluatedByIdPrefix.'-'.$value) ?>" value="<?= admin_e($value) ?>" <?= $evaluatedByValue === $value ? 'checked' : '' ?> class="evaluated-by-input mt-1 h-4 w-4 accent-admin-indigo">
                <span>
                    <span class="block text-sm font-bold text-slate-800"><?= admin_e($label) ?></span>
                    <span class="block text-xs text-slate-500"><?= admin_e($evaluatedByHelp[$value]) ?></span>
                </span>
            </label>
        <?php endforeach; ?>
    </div>
</fieldset>
