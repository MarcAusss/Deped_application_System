<?php
if (!defined('PORTAL_BOOTED')) { http_response_code(404); exit; }
/**
 * Shared form markup for job-position-create.php / job-position-edit.php.
 * Expects $errors (Errors), $old (closure(field, default) -> string) in
 * scope, and optionally $position (object) when editing.
 */
$position = $position ?? null;
$isEdit = $position !== null;
$action = $isEdit ? admin_url('job-position-edit.php?id='.$position->id) : admin_url('job-position-create.php');
$existingAttachments = $isEdit ? (json_decode($position->attachment_paths ?? '[]', true) ?: []) : [];
$existingCsc = $isEdit ? (json_decode($position->csc_publication_paths ?? '[]', true) ?: []) : [];
?>
<form method="POST" action="<?= $action ?>" enctype="multipart/form-data" class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:grid-cols-3">
    <?= admin_csrf_field() ?>

    <div class="sm:col-span-2">
        <label class="mb-1 block text-sm font-bold text-slate-700">Title</label>
        <input type="text" name="title" value="<?= admin_e($old('title', $position->title ?? '')) ?>" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <?php if ($errors->has('title')): ?><p class="mt-1 text-xs text-red-600"><?= admin_e($errors->first('title')) ?></p><?php endif; ?>
    </div>
    <div>
        <label class="mb-1 block text-sm font-bold text-slate-700">Acronym</label>
        <input type="text" name="abbreviation" placeholder="e.g. T-I" value="<?= admin_e($old('abbreviation', $position->abbreviation ?? '')) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
    </div>

    <div class="sm:col-span-3">
        <label class="mb-1 block text-sm font-bold text-slate-700">No. of Vacancies</label>
        <input type="number" name="slots" min="1" value="<?= admin_e($old('slots', (string) ($position->slots ?? 1))) ?>" required class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <?php if ($errors->has('slots')): ?><p class="mt-1 text-xs text-red-600"><?= admin_e($errors->first('slots')) ?></p><?php endif; ?>
    </div>

    <div class="sm:col-span-3">
        <label class="mb-1 block text-sm font-bold text-slate-700">Description</label>
        <textarea name="description" rows="3" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= admin_e($old('description', $position->description ?? '')) ?></textarea>
        <?php if ($errors->has('description')): ?><p class="mt-1 text-xs text-red-600"><?= admin_e($errors->first('description')) ?></p><?php endif; ?>
    </div>

    <div>
        <label class="mb-1 block text-sm font-bold text-slate-700">Posted</label>
        <input type="date" name="posted_at" value="<?= admin_e($old('posted_at', isset($position->posted_at) ? substr((string) $position->posted_at, 0, 10) : date('Y-m-d'))) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="mb-1 block text-sm font-bold text-slate-700">Until</label>
        <input type="date" name="until" value="<?= admin_e($old('until', isset($position->until) ? substr((string) $position->until, 0, 10) : '')) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <?php if ($errors->has('until')): ?><p class="mt-1 text-xs text-red-600"><?= admin_e($errors->first('until')) ?></p><?php endif; ?>
    </div>
    <div>
        <label class="mb-1 block text-sm font-bold text-slate-700">Closing Time</label>
        <input type="text" name="until_time" placeholder="e.g. 5:00 PM, 12 midnight, or 12 noon" value="<?= admin_e($old('until_time', isset($position->until_time) && $position->until_time ? date('g:i A', strtotime($position->until_time)) : '')) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <?php if ($errors->has('until_time')): ?><p class="mt-1 text-xs text-red-600"><?= admin_e($errors->first('until_time')) ?></p><?php endif; ?>
    </div>

    <div>
        <label class="mb-1 block text-sm font-bold text-slate-700">Salary Grade</label>
        <input type="text" name="salary_grade" placeholder="e.g. 11" value="<?= admin_e($old('salary_grade', $position->salary_grade ?? '')) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div class="sm:col-span-2">
        <label class="mb-1 block text-sm font-bold text-slate-700">Monthly Salary</label>
        <div class="flex items-center gap-2"><span class="text-sm text-slate-500">₱</span><input type="number" step="0.01" name="monthly_salary" value="<?= admin_e($old('monthly_salary', $position->monthly_salary ?? '')) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
    </div>

    <div class="sm:col-span-3">
        <label class="mb-1 block text-sm font-bold text-slate-700">Education Requirement</label>
        <textarea name="education_requirement" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= admin_e($old('education_requirement', $position->education_requirement ?? '')) ?></textarea>
    </div>
    <div class="sm:col-span-3">
        <label class="mb-1 block text-sm font-bold text-slate-700">Training Requirement</label>
        <textarea name="training_requirement" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= admin_e($old('training_requirement', $position->training_requirement ?? '')) ?></textarea>
    </div>
    <div class="sm:col-span-3">
        <label class="mb-1 block text-sm font-bold text-slate-700">Minimum Training Hours (QS)</label>
        <input type="number" min="0" name="min_training_hours" value="<?= admin_e($old('min_training_hours', $position->min_training_hours ?? '')) ?>" class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm">
    </div>

    <div class="sm:col-span-3">
        <label class="mb-1 block text-sm font-bold text-slate-700">Experience Requirement</label>
        <textarea name="experience_requirement" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= admin_e($old('experience_requirement', $position->experience_requirement ?? '')) ?></textarea>
    </div>
    <div class="sm:col-span-3">
        <label class="mb-1 block text-sm font-bold text-slate-700">Minimum Years of Experience (QS)</label>
        <input type="number" min="0" step="0.01" name="min_experience_years" value="<?= admin_e($old('min_experience_years', $position->min_experience_years ?? '')) ?>" class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm">
    </div>

    <div class="sm:col-span-3">
        <label class="mb-1 block text-sm font-bold text-slate-700">Eligibility Requirement</label>
        <textarea name="eligibility_requirement" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= admin_e($old('eligibility_requirement', $position->eligibility_requirement ?? '')) ?></textarea>
    </div>

    <div class="sm:col-span-3">
        <label class="mb-1 block text-sm font-bold text-slate-700">D.M Notice of Vacancy</label>
        <p class="mb-2 text-xs text-slate-500">Upload the official D.M Notice of Vacancy (PDF, multiple files allowed).</p>
        <?php if ($existingAttachments): ?>
            <ul class="mb-2 space-y-1">
                <?php foreach ($existingAttachments as $path): ?>
                    <li class="flex items-center gap-2 text-sm">
                        <label class="flex items-center gap-1.5"><input type="checkbox" name="remove_attachment_paths[]" value="<?= admin_e($path) ?>"> <span class="text-slate-500">Remove</span></label>
                        <a href="<?= admin_url('document.php?path='.urlencode($path)) ?>" target="_blank" class="text-admin-indigo underline"><?= admin_e(basename($path)) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <input type="file" name="attachment_paths[]" multiple accept="application/pdf" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <?php if ($errors->has('attachment_paths')): ?><p class="mt-1 text-xs text-red-600"><?= admin_e($errors->first('attachment_paths')) ?></p><?php endif; ?>
    </div>

    <div class="sm:col-span-3">
        <label class="mb-1 block text-sm font-bold text-slate-700">CSC Publication of Vacancy</label>
        <p class="mb-2 text-xs text-slate-500">Upload the official CSC Publication of Vacancy (PDF, multiple files allowed).</p>
        <?php if ($existingCsc): ?>
            <ul class="mb-2 space-y-1">
                <?php foreach ($existingCsc as $path): ?>
                    <li class="flex items-center gap-2 text-sm">
                        <label class="flex items-center gap-1.5"><input type="checkbox" name="remove_csc_publication_paths[]" value="<?= admin_e($path) ?>"> <span class="text-slate-500">Remove</span></label>
                        <a href="<?= admin_url('document.php?path='.urlencode($path)) ?>" target="_blank" class="text-admin-indigo underline"><?= admin_e(basename($path)) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <input type="file" name="csc_publication_paths[]" multiple accept="application/pdf" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
    </div>

    <div class="sm:col-span-3">
        <button type="submit" class="rounded-lg bg-admin-indigo px-5 py-2.5 text-sm font-bold text-white hover:bg-admin-indigoDark"><?= admin_e($submitLabel ?? ($isEdit ? 'Save Changes' : 'Create Job Position')) ?></button>
    </div>
</form>
