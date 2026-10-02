<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

admin_require_login();

$applicationId = (int) ($_GET['id'] ?? 0);
if (!$applicationId) {
    http_response_code(404);
    exit;
}

$pdo = portal_pdo();

$stmt = $pdo->prepare('SELECT * FROM applications WHERE id = ?');
$stmt->execute([$applicationId]);
$application = $stmt->fetch();
if (!$application) {
    http_response_code(404);
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM job_positions WHERE id = ?');
$stmt->execute([$application->job_position_id]);
$job = $stmt->fetch();

$stmt = $pdo->prepare('SELECT * FROM applicant_profiles WHERE application_id = ?');
$stmt->execute([$applicationId]);
$profile = $stmt->fetch();

$stmt = $pdo->prepare(
    'SELECT cn.control_number, u.name AS assigned_by
     FROM application_control_numbers cn
     LEFT JOIN users u ON u.id = cn.generated_by
     WHERE cn.application_id = ?'
);
$stmt->execute([$applicationId]);
$controlNumberRow = $stmt->fetch();

$stmt = $pdo->prepare(
    'SELECT e.*, u.name AS evaluator_name
     FROM application_evaluations e
     LEFT JOIN users u ON u.id = e.evaluator_id
     WHERE e.application_id = ?'
);
$stmt->execute([$applicationId]);
$evaluation = $stmt->fetch();

$fetchAll = function (string $table) use ($applicationId, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE application_id = ? ORDER BY id");
    $stmt->execute([$applicationId]);

    return $stmt->fetchAll();
};
$educations = $fetchAll('applicant_educations');
$experiences = $fetchAll('applicant_experiences');
$trainings = $fetchAll('applicant_trainings');
$eligibilities = $fetchAll('applicant_eligibilities');
$documents = $fetchAll('application_documents');

$mandatorySelected = $evaluation ? (json_decode($evaluation->documentary_mandatory ?? '[]', true) ?: []) : [];
$otherSelected = $evaluation ? (json_decode($evaluation->documentary_other ?? '[]', true) ?: []) : [];
$qsEducation = $evaluation ? eval_bool($evaluation->qs_education_met) : null;
$qsExperience = $evaluation ? eval_bool($evaluation->qs_experience_met) : null;
$qsTraining = $evaluation ? eval_bool($evaluation->qs_training_met) : null;
$qsEligibility = $evaluation ? eval_bool($evaluation->qs_eligibility_met) : null;
$documentaryComplete = eval_is_documentary_complete($mandatorySelected);

$result = $evaluation->result ?? EVAL_RESULT_PENDING;
$disqualifiedCategories = eval_disqualified_categories($qsEducation, $qsExperience, $qsTraining, $qsEligibility);

$colorHex = [
    'success' => ['bg' => '#dcfce7', 'fg' => '#15803d', 'badge' => '#15803d'],
    'danger' => ['bg' => '#fee2e2', 'fg' => '#b91c1c', 'badge' => '#b91c1c'],
    'gray' => ['bg' => '#f1f5f9', 'fg' => '#475569', 'badge' => '#475569'],
];
$resultStyle = $colorHex[eval_result_color($result)];

$eduRequirementFallback = "Bachelor's Degree in Guidance Counseling or Psychology; or any Bachelor's Degree with atleast eighteen (18) units of courses in Guidance and Psychology; or Any  Bachelor's Degree with a minimum of eighteen (18) units of Behavioral Science courses that shall include 200 hours of supervised practicum or internship experience on guidance and counseling, preferably in a school or community setting";
$eligRequirementFallback = 'Career Service Professional, Second Level Eligibility and RA 1080.';

function admin_met_badge(?bool $met): string
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

    return '<span style="'.$style.'" class="inline-block rounded-md px-2.5 py-1 text-xs font-bold">'.admin_e($label).'</span>';
}

$canDecide = in_array($application->status, ['evaluated', 'excluded'], true);

$pageTitle = ($profile->full_name ?? 'Application').' — View | Administration';
require __DIR__.'/_layout_head.php';
?>

<?php $activePage = ''; require __DIR__.'/_layout_topbar.php'; ?>

<div class="lg:flex lg:flex-1">
    <?php require __DIR__.'/_layout_sidebar.php'; ?>

    <div class="min-w-0 flex-1">
        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
            <a href="javascript:history.back()" class="mb-4 inline-flex items-center gap-1.5 text-sm font-bold text-slate-500 hover:text-admin-indigo">&larr; Back</a>

            <?php if (flash_get('success')): ?>
                <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800"><?= admin_e(flash_get('success')) ?></div>
            <?php endif; ?>
            <?php if (flash_get('error')): ?>
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800"><?= admin_e(flash_get('error')) ?></div>
            <?php endif; ?>

            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-2xl font-black text-admin-indigoDark"><?= admin_e($profile->full_name ?? 'Application Details') ?></h2>

                <div class="flex items-center gap-2">
                    <form method="POST" action="<?= admin_url('application-status.php') ?>" onsubmit="return confirm('Are you sure you want to mark this application Qualified? This finalizes the hiring decision.');">
                        <?= admin_csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $application->id ?>">
                        <input type="hidden" name="action" value="qualify">
                        <button type="submit" <?= $canDecide ? '' : 'disabled' ?> class="rounded-lg px-4 py-2 text-sm font-bold text-white <?= $canDecide ? 'bg-emerald-600 hover:bg-emerald-700' : 'cursor-not-allowed bg-slate-300' ?>">Mark Qualified</button>
                    </form>
                    <form method="POST" action="<?= admin_url('application-status.php') ?>" onsubmit="return confirm('Are you sure you want to mark this application Disqualified? This application will be moved to archive.');">
                        <?= admin_csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $application->id ?>">
                        <input type="hidden" name="action" value="disqualify">
                        <button type="submit" <?= $canDecide ? '' : 'disabled' ?> class="rounded-lg px-4 py-2 text-sm font-bold text-white <?= $canDecide ? 'bg-rose-600 hover:bg-rose-700' : 'cursor-not-allowed bg-slate-300' ?>">Mark Disqualified</button>
                    </form>
                </div>
            </div>

            <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-black text-admin-indigoDark">Application Info</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><p class="text-xs font-bold uppercase text-slate-500">Job Position</p><p class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= admin_e($job->title ?? '—') ?></p></div>
                    <div><p class="text-xs font-bold uppercase text-slate-500">Current Status</p><p class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= admin_e(ucfirst($application->status)) ?></p></div>
                </div>
            </section>

            <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-black text-admin-indigoDark">Control Number</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><p class="text-xs font-bold uppercase text-slate-500">Assigned Control Number</p><p class="mt-1 text-sm"><?= admin_e($controlNumberRow->control_number ?? 'Not yet assigned') ?></p></div>
                    <div><p class="text-xs font-bold uppercase text-slate-500">Assigned By</p><p class="mt-1 text-sm"><?= admin_e($controlNumberRow->assigned_by ?? '—') ?></p></div>
                </div>
            </section>

            <?php if ($evaluation): ?>
                <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-1 text-lg font-black text-admin-indigoDark">Documentary Requirements</h3>
                    <p class="mb-4 text-xs text-slate-500">Submitted by the evaluator. View only — admin cannot edit this.</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="mb-2 text-sm font-bold">Mandatory Requirements (<?= eval_count_selected(eval_mandatory_requirements(), $mandatorySelected) ?> of <?= eval_mandatory_count() ?>)</p>
                            <?php foreach (eval_mandatory_requirements() as $key => $label): ?>
                                <label class="mb-1 flex items-start gap-2 text-sm"><input type="checkbox" disabled <?= in_array($key, $mandatorySelected, true) ? 'checked' : '' ?> class="mt-1"><span><?= admin_e($label) ?></span></label>
                            <?php endforeach; ?>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="mb-2 text-sm font-bold">Other Requirements (<?= eval_count_selected(eval_other_requirements(), $otherSelected) ?> of <?= eval_other_count() ?>)</p>
                            <?php foreach (eval_other_requirements() as $key => $label): ?>
                                <label class="mb-1 flex items-start gap-2 text-sm"><input type="checkbox" disabled <?= in_array($key, $otherSelected, true) ? 'checked' : '' ?> class="mt-1"><span><?= admin_e($label) ?></span></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <?php if ($documentaryComplete): ?>
                <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-1 text-lg font-black text-admin-indigoDark">Qualification Standards</h3>
                    <p class="mb-4 text-xs text-slate-500">Review the applicant's details against each qualification standard. View only — admin cannot edit this.</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <?php
                        $qsBlocks = [
                            ["Bachelor's Degree", eval_applicant_bachelors_degree($educations), $job->education_requirement ?: $eduRequirementFallback, $qsEducation],
                            ['Years of Experience', eval_applicant_years_of_experience($experiences), ($job->min_experience_years ?? 0).' minimum year(s)', $qsExperience],
                            ['Hours of Training', eval_applicant_hours_of_training($trainings), ($job->min_training_hours ?? 0).' minimum hour(s)', $qsTraining],
                            ['Eligibility', eval_applicant_eligibility($eligibilities), $job->eligibility_requirement ?: $eligRequirementFallback, $qsEligibility],
                        ];
                        foreach ($qsBlocks as [$title, $applicantVal, $standardVal, $met]): ?>
                            <div class="rounded-xl border border-slate-200 p-4">
                                <p class="mb-2 font-black text-admin-indigoDark"><?= admin_e($title) ?></p>
                                <div class="mb-2 rounded-lg border border-slate-300 p-2 text-sm"><span class="block font-bold">Applicant</span><?= admin_e($applicantVal) ?></div>
                                <div class="mb-3 rounded-lg border border-slate-300 p-2 text-xs leading-relaxed"><span class="mb-0.5 block font-bold text-sm">Qualification Standard</span><?= admin_e($standardVal) ?></div>
                                <?= admin_met_badge($met) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
            <?php endif; ?>

            <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-black text-admin-indigoDark">Applicant Profile</h3>
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
                        <div class="rounded-lg border border-slate-300 p-2.5"><p class="text-sm font-bold text-slate-700"><?= admin_e($label) ?></p><span class="text-slate-600"><?= admin_e($value ?: '—') ?></span></div>
                    <?php endforeach; ?>
                </div>
            </section>

            <?php if ($evaluation): ?>
                <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-1 text-lg font-black text-admin-indigoDark">Evaluation Result</h3>
                    <p class="mb-4 text-xs text-slate-500">Completed by the evaluator. View only — admin cannot edit this.</p>
                    <div class="mb-4 rounded-lg border-2 px-4 py-2" style="background:<?= $resultStyle['bg'] ?>;border-color:<?= $resultStyle['fg'] ?>;">
                        <span class="inline-block rounded-md px-2.5 py-1 text-sm font-bold text-white" style="background:<?= $resultStyle['badge'] ?>;"><?= admin_e(strtoupper(eval_result_label($result))) ?></span>
                        <span class="ml-2 text-xs font-bold" style="color:#1e3a8a;"><?= admin_e(eval_result_description($result, $disqualifiedCategories)) ?></span>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><p class="text-xs font-bold uppercase text-slate-500">Evaluated By</p><p class="mt-1 text-sm"><?= admin_e($evaluation->evaluator_name ?? '—') ?></p></div>
                        <div><p class="text-xs font-bold uppercase text-slate-500">Evaluated On</p><p class="mt-1 text-sm"><?= $evaluation->evaluated_at ? admin_e(date('M d, Y h:i A', strtotime($evaluation->evaluated_at))) : '—' ?></p></div>
                    </div>
                    <div class="mt-4">
                        <p class="mb-1 text-sm font-bold text-slate-700">Remarks / Notes</p>
                        <div class="rounded-lg border border-slate-300 p-3 text-sm text-slate-600"><?= nl2br(admin_e($evaluation->remarks ?? '—')) ?></div>
                    </div>
                </section>
            <?php else: ?>
                <section class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-6">
                    <h3 class="mb-2 text-lg font-black text-amber-800">Evaluation Pending</h3>
                    <p class="text-sm text-amber-700">This application has not been evaluated yet. Qualify/Disqualify will become available once the evaluator submits their checklist.</p>
                </section>
            <?php endif; ?>

            <!-- Educational Background -->
            <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-black text-admin-indigoDark">Educational Background</h3>
                </div>
                <?php if (empty($educations)): ?>
                    <p class="mb-4 text-sm text-slate-500">No records submitted.</p>
                <?php else: ?>
                    <div class="mb-4 overflow-x-auto"><table class="w-full text-sm">
                        <thead><tr>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Level</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">School / Institution</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Degree / Course</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Year Graduated</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-center font-bold">Actions</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($educations as $e): ?>
                            <tr>
                                <td class="border-b border-slate-200 px-3 py-2"><?= admin_e($e->level === "Other's" && $e->level_specify ? $e->level.' - '.$e->level_specify : $e->level) ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= admin_e($e->school ?: '—') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= admin_e($e->degree ?: '—') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= admin_e($e->year_graduated ?: '—') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2 text-center">
                                    <details class="inline-block text-left"><summary class="inline cursor-pointer font-bold text-admin-indigo hover:underline">Edit</summary>
                                        <form method="POST" action="<?= admin_url('application-education.php') ?>" class="mt-3 grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
                                            <?= admin_csrf_field() ?>
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="id" value="<?= $e->id ?>">
                                            <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                                            <div><label class="mb-1 block text-xs font-bold text-slate-600">Education Level</label>
                                                <select name="level" class="edu-level w-full rounded border border-slate-300 px-2 py-1.5 text-sm" required>
                                                    <?php foreach (['Elementary', 'High School', 'Vocational', 'College', 'Post Graduate', "Other's"] as $lv): ?>
                                                        <option value="<?= admin_e($lv) ?>" <?= $e->level === $lv ? 'selected' : '' ?>><?= admin_e($lv) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="edu-specify"><label class="mb-1 block text-xs font-bold text-slate-600">Please specify</label><input type="text" name="level_specify" value="<?= admin_e($e->level_specify) ?>" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                                            <div><label class="mb-1 block text-xs font-bold text-slate-600">School / Institution</label><input type="text" name="school" value="<?= admin_e($e->school) ?>" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                                            <div><label class="mb-1 block text-xs font-bold text-slate-600">Degree / Course</label><input type="text" name="degree" value="<?= admin_e($e->degree) ?>" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                                            <div><label class="mb-1 block text-xs font-bold text-slate-600">Year Graduated</label><input type="number" name="year_graduated" value="<?= admin_e($e->year_graduated) ?>" min="1900" max="<?= date('Y') ?>" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                                            <div class="sm:col-span-2"><button type="submit" class="rounded-lg bg-admin-indigo px-4 py-1.5 text-sm font-bold text-white hover:bg-admin-indigoDark">Save</button></div>
                                        </form>
                                    </details>
                                    <form method="POST" action="<?= admin_url('application-education.php') ?>" class="inline" onsubmit="return confirm('Delete this record?');">
                                        <?= admin_csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $e->id ?>">
                                        <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                                        <button type="submit" class="ml-2 font-bold text-rose-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php endif; ?>
                <details><summary class="cursor-pointer font-bold text-admin-indigo hover:underline">+ Add New</summary>
                    <form method="POST" action="<?= admin_url('application-education.php') ?>" class="mt-3 grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
                        <?= admin_csrf_field() ?>
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                        <div><label class="mb-1 block text-xs font-bold text-slate-600">Education Level</label>
                            <select name="level" class="edu-level w-full rounded border border-slate-300 px-2 py-1.5 text-sm" required>
                                <option value="">Select level</option>
                                <?php foreach (['Elementary', 'High School', 'Vocational', 'College', 'Post Graduate', "Other's"] as $lv): ?>
                                    <option value="<?= admin_e($lv) ?>"><?= admin_e($lv) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="edu-specify"><label class="mb-1 block text-xs font-bold text-slate-600">Please specify</label><input type="text" name="level_specify" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                        <div><label class="mb-1 block text-xs font-bold text-slate-600">School / Institution</label><input type="text" name="school" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                        <div><label class="mb-1 block text-xs font-bold text-slate-600">Degree / Course</label><input type="text" name="degree" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                        <div><label class="mb-1 block text-xs font-bold text-slate-600">Year Graduated</label><input type="number" name="year_graduated" min="1900" max="<?= date('Y') ?>" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                        <div class="sm:col-span-2"><button type="submit" class="rounded-lg bg-admin-indigo px-4 py-1.5 text-sm font-bold text-white hover:bg-admin-indigoDark">Add</button></div>
                    </form>
                </details>
            </section>

            <!-- Work Experience -->
            <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-black text-admin-indigoDark">Work Experience</h3>
                <?php if (empty($experiences)): ?>
                    <p class="mb-4 text-sm text-slate-500">No records submitted.</p>
                <?php else: ?>
                    <div class="mb-4 overflow-x-auto"><table class="w-full text-sm">
                        <thead><tr>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Job Title</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Company</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">First Day</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Last Day</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Details</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-center font-bold">Actions</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($experiences as $x): ?>
                            <tr>
                                <td class="border-b border-slate-200 px-3 py-2"><?= admin_e($x->title ?: '—') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= admin_e($x->company ?: '—') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= admin_e($x->first_day ?: 'Not provided') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= admin_e($x->last_day ?: 'Not provided') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= admin_e($x->details ?: 'Not provided') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2 text-center">
                                    <details class="inline-block text-left"><summary class="inline cursor-pointer font-bold text-admin-indigo hover:underline">Edit</summary>
                                        <form method="POST" action="<?= admin_url('application-experience.php') ?>" class="mt-3 grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
                                            <?= admin_csrf_field() ?>
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="id" value="<?= $x->id ?>">
                                            <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                                            <div><label class="mb-1 block text-xs font-bold text-slate-600">Job Title</label><input type="text" name="title" value="<?= admin_e($x->title) ?>" required class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                                            <div><label class="mb-1 block text-xs font-bold text-slate-600">Company / Organization</label><input type="text" name="company" value="<?= admin_e($x->company) ?>" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                                            <div><label class="mb-1 block text-xs font-bold text-slate-600">First Day of Service</label><input type="text" name="first_day" value="<?= admin_e($x->first_day) ?>" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                                            <div><label class="mb-1 block text-xs font-bold text-slate-600">Last Day of Service</label><input type="text" name="last_day" value="<?= admin_e($x->last_day) ?>" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                                            <div><label class="mb-1 block text-xs font-bold text-slate-600">Duration (e.g. 2 years 3 months)</label><input type="text" name="years_months" value="<?= admin_e($x->years_months) ?>" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                                            <div class="sm:col-span-2"><label class="mb-1 block text-xs font-bold text-slate-600">Responsibilities or Details</label><textarea name="details" rows="3" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"><?= admin_e($x->details) ?></textarea></div>
                                            <div class="sm:col-span-2"><button type="submit" class="rounded-lg bg-admin-indigo px-4 py-1.5 text-sm font-bold text-white hover:bg-admin-indigoDark">Save</button></div>
                                        </form>
                                    </details>
                                    <form method="POST" action="<?= admin_url('application-experience.php') ?>" class="inline" onsubmit="return confirm('Delete this record?');">
                                        <?= admin_csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $x->id ?>">
                                        <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                                        <button type="submit" class="ml-2 font-bold text-rose-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php endif; ?>
                <details><summary class="cursor-pointer font-bold text-admin-indigo hover:underline">+ Add New</summary>
                    <form method="POST" action="<?= admin_url('application-experience.php') ?>" class="mt-3 grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
                        <?= admin_csrf_field() ?>
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                        <div><label class="mb-1 block text-xs font-bold text-slate-600">Job Title</label><input type="text" name="title" required class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                        <div><label class="mb-1 block text-xs font-bold text-slate-600">Company / Organization</label><input type="text" name="company" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                        <div><label class="mb-1 block text-xs font-bold text-slate-600">First Day of Service</label><input type="text" name="first_day" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                        <div><label class="mb-1 block text-xs font-bold text-slate-600">Last Day of Service</label><input type="text" name="last_day" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                        <div><label class="mb-1 block text-xs font-bold text-slate-600">Duration (e.g. 2 years 3 months)</label><input type="text" name="years_months" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                        <div class="sm:col-span-2"><label class="mb-1 block text-xs font-bold text-slate-600">Responsibilities or Details</label><textarea name="details" rows="3" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></textarea></div>
                        <div class="sm:col-span-2"><button type="submit" class="rounded-lg bg-admin-indigo px-4 py-1.5 text-sm font-bold text-white hover:bg-admin-indigoDark">Add</button></div>
                    </form>
                </details>
            </section>

            <!-- Trainings & Seminars -->
            <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-black text-admin-indigoDark">Trainings &amp; Seminars</h3>
                <?php if (empty($trainings)): ?>
                    <p class="mb-4 text-sm text-slate-500">No records submitted.</p>
                <?php else: ?>
                    <div class="mb-4 overflow-x-auto"><table class="w-full text-sm">
                        <thead><tr>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Training Title</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Hours</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Start</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">End</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-center font-bold">Actions</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($trainings as $t): ?>
                            <tr>
                                <td class="border-b border-slate-200 px-3 py-2"><?= admin_e($t->title ?: '—') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= admin_e(($t->hours ?: '0').' hrs') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= $t->training_date ? admin_e(date('F Y', strtotime($t->training_date))) : 'Not provided' ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= $t->training_end_date ? admin_e(date('F Y', strtotime($t->training_end_date))) : 'Not provided' ?></td>
                                <td class="border-b border-slate-200 px-3 py-2 text-center">
                                    <details class="inline-block text-left"><summary class="inline cursor-pointer font-bold text-admin-indigo hover:underline">Edit</summary>
                                        <form method="POST" action="<?= admin_url('application-training.php') ?>" class="mt-3 grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
                                            <?= admin_csrf_field() ?>
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="id" value="<?= $t->id ?>">
                                            <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                                            <div class="sm:col-span-2"><label class="mb-1 block text-xs font-bold text-slate-600">Training / Seminar Title</label><input type="text" name="title" value="<?= admin_e($t->title) ?>" required class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                                            <div><label class="mb-1 block text-xs font-bold text-slate-600">Number of Hours</label><input type="number" name="hours" value="<?= admin_e((string) $t->hours) ?>" min="1" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                                            <div></div>
                                            <div><label class="mb-1 block text-xs font-bold text-slate-600">Start of Training</label><input type="month" name="training_date" value="<?= $t->training_date ? date('Y-m', strtotime($t->training_date)) : '' ?>" max="<?= date('Y-m') ?>" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                                            <div><label class="mb-1 block text-xs font-bold text-slate-600">End of Training</label><input type="month" name="training_end_date" value="<?= $t->training_end_date ? date('Y-m', strtotime($t->training_end_date)) : '' ?>" max="<?= date('Y-m') ?>" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                                            <div class="sm:col-span-2"><button type="submit" class="rounded-lg bg-admin-indigo px-4 py-1.5 text-sm font-bold text-white hover:bg-admin-indigoDark">Save</button></div>
                                        </form>
                                    </details>
                                    <form method="POST" action="<?= admin_url('application-training.php') ?>" class="inline" onsubmit="return confirm('Delete this record?');">
                                        <?= admin_csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $t->id ?>">
                                        <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                                        <button type="submit" class="ml-2 font-bold text-rose-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php endif; ?>
                <details><summary class="cursor-pointer font-bold text-admin-indigo hover:underline">+ Add New</summary>
                    <form method="POST" action="<?= admin_url('application-training.php') ?>" class="mt-3 grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
                        <?= admin_csrf_field() ?>
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                        <div class="sm:col-span-2"><label class="mb-1 block text-xs font-bold text-slate-600">Training / Seminar Title</label><input type="text" name="title" required class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                        <div><label class="mb-1 block text-xs font-bold text-slate-600">Number of Hours</label><input type="number" name="hours" min="1" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                        <div></div>
                        <div><label class="mb-1 block text-xs font-bold text-slate-600">Start of Training</label><input type="month" name="training_date" max="<?= date('Y-m') ?>" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                        <div><label class="mb-1 block text-xs font-bold text-slate-600">End of Training</label><input type="month" name="training_end_date" max="<?= date('Y-m') ?>" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                        <div class="sm:col-span-2"><button type="submit" class="rounded-lg bg-admin-indigo px-4 py-1.5 text-sm font-bold text-white hover:bg-admin-indigoDark">Add</button></div>
                    </form>
                </details>
            </section>

            <!-- Eligibilities / Licenses -->
            <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-black text-admin-indigoDark">Eligibilities / Licenses</h3>
                <?php if (empty($eligibilities)): ?>
                    <p class="mb-4 text-sm text-slate-500">No records submitted.</p>
                <?php else: ?>
                    <div class="mb-4 overflow-x-auto"><table class="w-full text-sm">
                        <thead><tr>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">License / Eligibility</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Rating</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Date Issued</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Valid Until</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-center font-bold">Actions</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($eligibilities as $el): ?>
                            <tr>
                                <td class="border-b border-slate-200 px-3 py-2"><?= admin_e(in_array($el->license_name, ['RA1080', "Other's"], true) && $el->license_specify ? $el->license_name.' - '.$el->license_specify : $el->license_name) ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= admin_e($el->rating ?: '—') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= $el->date_issued ? admin_e(date('M d, Y', strtotime($el->date_issued))) : '—' ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= eval_bool($el->never_expires) ? 'Never Expires' : ($el->valid_until ? admin_e(date('M d, Y', strtotime($el->valid_until))) : '—') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2 text-center">
                                    <details class="inline-block text-left"><summary class="inline cursor-pointer font-bold text-admin-indigo hover:underline">Edit</summary>
                                        <form method="POST" action="<?= admin_url('application-eligibility.php') ?>" class="mt-3 grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
                                            <?= admin_csrf_field() ?>
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="id" value="<?= $el->id ?>">
                                            <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                                            <div><label class="mb-1 block text-xs font-bold text-slate-600">License / Eligibility Name</label>
                                                <select name="license_name" class="elig-name w-full rounded border border-slate-300 px-2 py-1.5 text-sm" required>
                                                    <?php foreach (['CS Sub-Professional', 'CSCS Professional', 'RA1080', "Other's"] as $ln): ?>
                                                        <option value="<?= admin_e($ln) ?>" <?= $el->license_name === $ln ? 'selected' : '' ?>><?= admin_e($ln) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="elig-specify"><label class="mb-1 block text-xs font-bold text-slate-600">Please specify</label><input type="text" name="license_specify" value="<?= admin_e($el->license_specify) ?>" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                                            <div><label class="mb-1 block text-xs font-bold text-slate-600">Rating</label><input type="text" name="rating" value="<?= admin_e($el->rating) ?>" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                                            <div><label class="mb-1 block text-xs font-bold text-slate-600">Date Issued</label><input type="date" name="date_issued" value="<?= admin_e($el->date_issued) ?>" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                                            <div class="elig-valid"><label class="mb-1 block text-xs font-bold text-slate-600">Valid Until</label><input type="date" name="valid_until" value="<?= admin_e($el->valid_until) ?>" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                                            <div class="flex items-end"><label class="flex items-center gap-2 text-sm font-bold text-slate-600"><input type="checkbox" name="never_expires" value="1" class="elig-never" <?= eval_bool($el->never_expires) ? 'checked' : '' ?>> Never Expires</label></div>
                                            <div class="sm:col-span-2"><button type="submit" class="rounded-lg bg-admin-indigo px-4 py-1.5 text-sm font-bold text-white hover:bg-admin-indigoDark">Save</button></div>
                                        </form>
                                    </details>
                                    <form method="POST" action="<?= admin_url('application-eligibility.php') ?>" class="inline" onsubmit="return confirm('Delete this record?');">
                                        <?= admin_csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $el->id ?>">
                                        <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                                        <button type="submit" class="ml-2 font-bold text-rose-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php endif; ?>
                <details><summary class="cursor-pointer font-bold text-admin-indigo hover:underline">+ Add New</summary>
                    <form method="POST" action="<?= admin_url('application-eligibility.php') ?>" class="mt-3 grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
                        <?= admin_csrf_field() ?>
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                        <div><label class="mb-1 block text-xs font-bold text-slate-600">License / Eligibility Name</label>
                            <select name="license_name" class="elig-name w-full rounded border border-slate-300 px-2 py-1.5 text-sm" required>
                                <option value="">Select</option>
                                <?php foreach (['CS Sub-Professional', 'CSCS Professional', 'RA1080', "Other's"] as $ln): ?>
                                    <option value="<?= admin_e($ln) ?>"><?= admin_e($ln) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="elig-specify"><label class="mb-1 block text-xs font-bold text-slate-600">Please specify</label><input type="text" name="license_specify" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                        <div><label class="mb-1 block text-xs font-bold text-slate-600">Rating</label><input type="text" name="rating" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                        <div><label class="mb-1 block text-xs font-bold text-slate-600">Date Issued</label><input type="date" name="date_issued" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                        <div class="elig-valid"><label class="mb-1 block text-xs font-bold text-slate-600">Valid Until</label><input type="date" name="valid_until" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                        <div class="flex items-end"><label class="flex items-center gap-2 text-sm font-bold text-slate-600"><input type="checkbox" name="never_expires" value="1" class="elig-never"> Never Expires</label></div>
                        <div class="sm:col-span-2"><button type="submit" class="rounded-lg bg-admin-indigo px-4 py-1.5 text-sm font-bold text-white hover:bg-admin-indigoDark">Add</button></div>
                    </form>
                </details>
            </section>

            <!-- Submitted Documents -->
            <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-black text-admin-indigoDark">Submitted Documents</h3>
                <?php if (empty($documents)): ?>
                    <p class="mb-4 text-sm text-slate-500">No records submitted.</p>
                <?php else: ?>
                    <div class="mb-4 overflow-x-auto"><table class="w-full text-sm">
                        <thead><tr>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Document Type</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">File</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-center font-bold">Actions</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td class="border-b border-slate-200 px-3 py-2"><span class="pill bg-admin-indigoLight text-admin-indigo"><?= admin_e($doc->type) ?></span></td>
                                <td class="border-b border-slate-200 px-3 py-2"><a href="<?= admin_url('document.php?path='.urlencode($doc->file_path)) ?>" target="_blank" rel="noopener" class="text-admin-indigo underline"><?= admin_e(basename($doc->file_path)) ?></a></td>
                                <td class="border-b border-slate-200 px-3 py-2 text-center">
                                    <form method="POST" action="<?= admin_url('application-documents.php') ?>" class="inline" onsubmit="return confirm('Delete this document?');">
                                        <?= admin_csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $doc->id ?>">
                                        <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                                        <button type="submit" class="font-bold text-rose-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php endif; ?>
                <details><summary class="cursor-pointer font-bold text-admin-indigo hover:underline">+ Add New</summary>
                    <form method="POST" action="<?= admin_url('application-documents.php') ?>" enctype="multipart/form-data" class="mt-3 grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
                        <?= admin_csrf_field() ?>
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                        <div><label class="mb-1 block text-xs font-bold text-slate-600">Document Type</label>
                            <select name="type" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm" required>
                                <?php foreach (admin_document_types() as $k => $v): ?>
                                    <option value="<?= admin_e($k) ?>"><?= admin_e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div><label class="mb-1 block text-xs font-bold text-slate-600">Upload File</label><input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"></div>
                        <div class="sm:col-span-2"><button type="submit" class="rounded-lg bg-admin-indigo px-4 py-1.5 text-sm font-bold text-white hover:bg-admin-indigoDark">Upload</button></div>
                    </form>
                </details>
            </section>
        </main>
    </div>
</div>

<script>
    function bindConditional(selectSelector, specifyClass, matchValues) {
        document.querySelectorAll(selectSelector).forEach(function (select) {
            var wrap = select.closest('form').querySelector('.' + specifyClass);
            function update() {
                if (!wrap) return;
                wrap.style.display = matchValues.includes(select.value) ? '' : 'none';
            }
            select.addEventListener('change', update);
            update();
        });
    }
    bindConditional('.edu-level', 'edu-specify', ["Other's"]);
    bindConditional('.elig-name', 'elig-specify', ['RA1080', "Other's"]);

    document.querySelectorAll('.elig-never').forEach(function (chk) {
        var validWrap = chk.closest('form').querySelector('.elig-valid');
        function update() {
            if (!validWrap) return;
            validWrap.style.display = chk.checked ? 'none' : '';
        }
        chk.addEventListener('change', update);
        update();
    });
</script>

<?php require __DIR__.'/_layout_footer.php'; ?>
