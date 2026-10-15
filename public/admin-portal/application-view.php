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

$stmt = $pdo->prepare('SELECT control_number FROM application_control_numbers WHERE application_id = ?');
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
$missingMandatory = $result === EVAL_RESULT_EXCLUDED ? eval_missing_mandatory_requirements($mandatorySelected) : [];

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

/**
 * Heroicons outline paths (24x24, stroke-width 1.5), keyed by name — used by
 * admin_section_header() for the colored section-header bars.
 */
function admin_section_icon(string $name): string
{
    $paths = [
        'briefcase' => 'M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0',
        'hashtag' => 'M5.25 8.25h15m-16.5 7.5h15m-1.8-13.5-3.9 19.5m-2.1-19.5-3.9 19.5',
        'clipboard' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.03 0 1.9.693 2.166 1.638m-5.8 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664M6.75 7.5h10.5a2.25 2.25 0 0 1 2.25 2.25v10.5a2.25 2.25 0 0 1-2.25 2.25H6.75a2.25 2.25 0 0 1-2.25-2.25V9.75A2.25 2.25 0 0 1 6.75 7.5Z',
        'scale' => 'M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971Zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971Z',
        'user' => 'M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
        'flag' => 'M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5',
        'academic-cap' => 'M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5',
        'building' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21',
        'presentation' => 'M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5',
        'id-card' => 'M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z',
        'paperclip' => 'm18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.5L8.552 18.448a1.5 1.5 0 0 1-2.122-2.122l9.546-9.546',
    ];

    return $paths[$name] ?? '';
}

/**
 * Renders a full-width colored header bar (icon + title + optional
 * subtitle) for the top of a section card — mirrors the source system's
 * Filament Infolist section headers.
 */
function admin_section_header(string $title, string $icon, ?string $subtitle = null, string $actionsHtml = ''): string
{
    $sub = $subtitle ? '<p class="mt-0.5 text-xs text-indigo-200">'.admin_e($subtitle).'</p>' : '';
    $actions = $actionsHtml ? '<div class="ml-auto flex-shrink-0">'.$actionsHtml.'</div>' : '';

    return '<div class="flex items-center gap-3 bg-admin-indigoDark px-6 py-4">'
        .'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6 flex-shrink-0 text-white"><path stroke-linecap="round" stroke-linejoin="round" d="'.admin_section_icon($icon).'" /></svg>'
        .'<div><h3 class="text-lg font-black text-white">'.admin_e($title).'</h3>'.$sub.'</div>'
        .$actions
        .'</div>';
}

/**
 * Renders one requirement row for the read-only Documentary Requirements
 * checklist — a green bordered card with a check icon when selected, a
 * plain gray box when not.
 */
function admin_requirement_box(string $label, bool $checked): string
{
    $box = $checked
        ? '<span class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded bg-emerald-600 text-white"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="h-3 w-3"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg></span>'
        : '<span class="mt-0.5 h-5 w-5 flex-shrink-0 rounded border border-slate-300 bg-white"></span>';
    $boxClass = $checked ? 'border-emerald-300 bg-emerald-50' : 'border-slate-200';
    $textClass = $checked ? 'font-bold text-indigo-700' : 'text-slate-500';

    return '<div class="flex items-start gap-2 rounded-lg border '.$boxClass.' px-3 py-2.5 text-sm">'.$box.'<span class="'.$textClass.'">'.admin_e($label).'</span></div>';
}

$canDecide = in_array($application->status, ['evaluated', 'excluded'], true);

// Applicant-submitted records (education, experience, trainings,
// eligibilities, documents) are always shown read-only here — no Edit/
// Delete/Add New controls on this view, for any application status.
$isFinalized = true;

// embed=1 is used when this page is loaded inside another page's modal
// iframe (e.g. the "View" button on Applications/Approvals/Archive) — skip
// the topbar/sidebar chrome and back-link so only the content renders.
$embed = ($_GET['embed'] ?? '') === '1';

$pageTitle = ($profile->full_name ?? 'Application').' — View | Administration';
require __DIR__.'/_layout_head.php';
?>

<?php if (!$embed): ?>
<?php $activePage = ''; require __DIR__.'/_layout_topbar.php'; ?>

<div class="lg:flex lg:flex-1">
    <?php require __DIR__.'/_layout_sidebar.php'; ?>
<?php endif; ?>

    <div class="min-w-0 flex-1">
        <main class="px-4 py-8 sm:px-6 lg:px-10">
            <?php if (!$embed): ?>
            <a href="javascript:history.back()" class="mb-4 inline-flex items-center gap-1.5 text-sm font-bold text-slate-500 hover:text-admin-indigo">&larr; Back</a>
            <?php endif; ?>

            <?php if (flash_get('success')): ?>
                <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800"><?= admin_e(flash_get('success')) ?></div>
            <?php endif; ?>
            <?php if (flash_get('error')): ?>
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800"><?= admin_e(flash_get('error')) ?></div>
            <?php endif; ?>

            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-2xl font-black text-admin-indigoDark"><?= admin_e($profile->full_name ?? 'Application Details') ?></h2>

                <div class="flex items-center gap-2">
                    <form method="POST" action="<?= admin_url('application-status.php') ?>" data-confirm="Are you sure you want to mark this application Qualified? This finalizes the hiring decision.">
                        <?= admin_csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $application->id ?>">
                        <input type="hidden" name="action" value="qualify">
                        <button type="submit" <?= $canDecide ? '' : 'disabled' ?> class="rounded-lg px-4 py-2 text-sm font-bold text-white <?= $canDecide ? 'bg-emerald-600 hover:bg-emerald-700' : 'cursor-not-allowed bg-slate-300' ?>">Mark Qualified</button>
                    </form>
                    <form method="POST" action="<?= admin_url('application-status.php') ?>" data-confirm="Are you sure you want to mark this application Disqualified? This application will be moved to archive.">
                        <?= admin_csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $application->id ?>">
                        <input type="hidden" name="action" value="disqualify">
                        <button type="submit" <?= $canDecide ? '' : 'disabled' ?> class="rounded-lg px-4 py-2 text-sm font-bold text-white <?= $canDecide ? 'bg-rose-600 hover:bg-rose-700' : 'cursor-not-allowed bg-slate-300' ?>">Mark Disqualified</button>
                    </form>
                </div>
            </div>

            <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <?= admin_section_header('Application Info', 'briefcase') ?>
                <div class="grid gap-4 p-6 sm:grid-cols-2">
                    <div><p class="text-xs font-bold uppercase text-slate-500">Job Position</p><p class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= admin_e($job->title ?? '—') ?></p></div>
                    <div><p class="text-xs font-bold uppercase text-slate-500">Current Status</p><p class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= admin_e(ucfirst($application->status)) ?></p></div>
                </div>
            </section>

            <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <?= admin_section_header('Control Number', 'hashtag') ?>
                <div class="p-6">
                    <p class="text-xs font-bold uppercase text-slate-500">Assigned Control Number</p><p class="mt-1 text-sm"><?= admin_e($controlNumberRow->control_number ?? 'Not yet assigned') ?></p>
                </div>
            </section>

            <?php if ($evaluation): ?>
                <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <?= admin_section_header('Documentary Requirements', 'clipboard', 'Submitted by the evaluator. View only — admin cannot edit this.') ?>
                    <div class="grid gap-4 p-6 sm:grid-cols-2">
                        <div>
                            <p class="mb-2 text-sm font-bold">Mandatory Requirements (<?= eval_count_selected(eval_mandatory_requirements(), $mandatorySelected) ?> of <?= eval_mandatory_count() ?>)</p>
                            <div class="grid gap-2">
                                <?php foreach (eval_mandatory_requirements() as $key => $label): ?>
                                    <?= admin_requirement_box($label, in_array($key, $mandatorySelected, true)) ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div>
                            <p class="mb-2 text-sm font-bold">Other Requirements (<?= eval_count_selected(eval_other_requirements(), $otherSelected) ?> of <?= eval_other_count() ?>)</p>
                            <div class="grid gap-2">
                                <?php foreach (eval_other_requirements() as $key => $label): ?>
                                    <?= admin_requirement_box($label, in_array($key, $otherSelected, true)) ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </section>

                <?php if ($documentaryComplete): ?>
                <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <?= admin_section_header('Qualification Standards', 'scale', "Review the applicant's details against each qualification standard. View only — admin cannot edit this.") ?>
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

            <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <?= admin_section_header('Applicant Profile', 'user') ?>
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
                        <div class="rounded-lg border border-slate-300 p-2.5"><p class="text-sm font-bold text-slate-700"><?= admin_e($label) ?></p><span class="text-slate-600"><?= admin_e($value ?: '—') ?></span></div>
                    <?php endforeach; ?>
                </div>
            </section>

            <?php if ($evaluation): ?>
                <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <?= admin_section_header('Evaluation Result', 'flag', 'Completed by the evaluator. View only — admin cannot edit this.') ?>
                    <div class="p-6">
                        <div class="mb-4 rounded-lg border-2 px-4 py-2" style="background:<?= $resultStyle['bg'] ?>;border-color:<?= $resultStyle['fg'] ?>;">
                            <span class="inline-block rounded-md px-2.5 py-1 text-sm font-bold text-white" style="background:<?= $resultStyle['badge'] ?>;"><?= admin_e(strtoupper(eval_result_label($result))) ?></span>
                            <span class="ml-2 text-xs font-bold" style="color:#1e3a8a;"><?= admin_e(eval_result_description($result, $disqualifiedCategories, $missingMandatory)) ?></span>
                        </div>
                        <?php if ($missingMandatory): ?>
                            <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-4">
                                <p class="mb-2 text-xs font-bold uppercase tracking-wide text-rose-700">Missing Mandatory Requirements</p>
                                <ul class="list-inside list-disc space-y-1 text-sm text-rose-700">
                                    <?php foreach ($missingMandatory as $item): ?>
                                        <li><?= admin_e($item) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div><p class="text-xs font-bold uppercase text-slate-500">Evaluated By</p><p class="mt-1 text-sm"><?= admin_e($evaluation->evaluator_name ?? '—') ?></p></div>
                            <div><p class="text-xs font-bold uppercase text-slate-500">Evaluated On</p><p class="mt-1 text-sm"><?= $evaluation->evaluated_at ? admin_e(date('M d, Y h:i A', strtotime($evaluation->evaluated_at))) : '—' ?></p></div>
                        </div>
                        <div class="mt-4">
                            <p class="mb-1 text-sm font-bold text-slate-700">Remarks / Notes</p>
                            <div class="rounded-lg border border-slate-300 p-3 text-sm text-slate-600"><?= nl2br(admin_e($evaluation->remarks ?? '—')) ?></div>
                        </div>
                    </div>
                </section>
            <?php else: ?>
                <section class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-6">
                    <h3 class="mb-2 text-lg font-black text-amber-800">Evaluation Pending</h3>
                    <p class="text-sm text-amber-700">This application has not been evaluated yet. Qualify/Disqualify will become available once the evaluator submits their checklist.</p>
                </section>
            <?php endif; ?>

            <!-- Educational Background -->
            <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <?= admin_section_header('Educational Background', 'academic-cap') ?>
                <div class="p-6">
                <?php if (empty($educations)): ?>
                    <p class="mb-4 text-sm text-slate-500">No records submitted.</p>
                <?php else: ?>
                    <div class="mb-4 overflow-x-auto"><table class="w-full text-sm">
                        <thead><tr>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Level</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">School / Institution</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Degree / Course</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Year Graduated</th>
                            <?php if (!$isFinalized): ?><th class="border-b-2 border-slate-300 px-3 py-2 text-center font-bold">Actions</th><?php endif; ?>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($educations as $e): ?>
                            <tr>
                                <td class="border-b border-slate-200 px-3 py-2"><?= admin_e($e->level === "Other's" && $e->level_specify ? $e->level.' - '.$e->level_specify : $e->level) ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= admin_e($e->school ?: '—') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= admin_e($e->degree ?: '—') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= admin_e($e->year_graduated ?: '—') ?></td>
                                <?php if (!$isFinalized): ?>
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
                                    <form method="POST" action="<?= admin_url('application-education.php') ?>" class="inline" data-confirm="Delete this record?">
                                        <?= admin_csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $e->id ?>">
                                        <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                                        <button type="submit" class="ml-2 font-bold text-rose-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php endif; ?>
                <?php if (!$isFinalized): ?>
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
                <?php endif; ?>
                </div>
            </section>

            <!-- Work Experience -->
            <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <?= admin_section_header('Work Experience', 'building') ?>
                <div class="p-6">
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
                            <?php if (!$isFinalized): ?><th class="border-b-2 border-slate-300 px-3 py-2 text-center font-bold">Actions</th><?php endif; ?>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($experiences as $x): ?>
                            <tr>
                                <td class="border-b border-slate-200 px-3 py-2"><?= admin_e($x->title ?: '—') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= admin_e($x->company ?: '—') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= admin_e($x->first_day ?: 'Not provided') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= admin_e($x->last_day ?: 'Not provided') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= admin_e($x->details ?: 'Not provided') ?></td>
                                <?php if (!$isFinalized): ?>
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
                                    <form method="POST" action="<?= admin_url('application-experience.php') ?>" class="inline" data-confirm="Delete this record?">
                                        <?= admin_csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $x->id ?>">
                                        <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                                        <button type="submit" class="ml-2 font-bold text-rose-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php endif; ?>
                <?php if (!$isFinalized): ?>
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
                <?php endif; ?>
                </div>
            </section>

            <!-- Trainings & Seminars -->
            <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <?= admin_section_header('Trainings & Seminars', 'presentation') ?>
                <div class="p-6">
                <?php if (empty($trainings)): ?>
                    <p class="mb-4 text-sm text-slate-500">No records submitted.</p>
                <?php else: ?>
                    <div class="mb-4 overflow-x-auto"><table class="w-full text-sm">
                        <thead><tr>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Training Title</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Hours</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Start</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">End</th>
                            <?php if (!$isFinalized): ?><th class="border-b-2 border-slate-300 px-3 py-2 text-center font-bold">Actions</th><?php endif; ?>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($trainings as $t): ?>
                            <tr>
                                <td class="border-b border-slate-200 px-3 py-2"><?= admin_e($t->title ?: '—') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= admin_e(($t->hours ?: '0').' hrs') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= $t->training_date ? admin_e(date('F Y', strtotime($t->training_date))) : 'Not provided' ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= $t->training_end_date ? admin_e(date('F Y', strtotime($t->training_end_date))) : 'Not provided' ?></td>
                                <?php if (!$isFinalized): ?>
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
                                    <form method="POST" action="<?= admin_url('application-training.php') ?>" class="inline" data-confirm="Delete this record?">
                                        <?= admin_csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $t->id ?>">
                                        <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                                        <button type="submit" class="ml-2 font-bold text-rose-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php endif; ?>
                <?php if (!$isFinalized): ?>
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
                <?php endif; ?>
                </div>
            </section>

            <!-- Eligibilities / Licenses -->
            <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <?= admin_section_header('Eligibilities / Licenses', 'id-card') ?>
                <div class="p-6">
                <?php if (empty($eligibilities)): ?>
                    <p class="mb-4 text-sm text-slate-500">No records submitted.</p>
                <?php else: ?>
                    <div class="mb-4 overflow-x-auto"><table class="w-full text-sm">
                        <thead><tr>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">License / Eligibility</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Rating</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Date Issued</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Valid Until</th>
                            <?php if (!$isFinalized): ?><th class="border-b-2 border-slate-300 px-3 py-2 text-center font-bold">Actions</th><?php endif; ?>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($eligibilities as $el): ?>
                            <tr>
                                <td class="border-b border-slate-200 px-3 py-2"><?= admin_e(in_array($el->license_name, ['RA1080', "Other's"], true) && $el->license_specify ? $el->license_name.' - '.$el->license_specify : $el->license_name) ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= admin_e($el->rating ?: '—') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= $el->date_issued ? admin_e(date('M d, Y', strtotime($el->date_issued))) : '—' ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= eval_bool($el->never_expires) ? 'Never Expires' : ($el->valid_until ? admin_e(date('M d, Y', strtotime($el->valid_until))) : '—') ?></td>
                                <?php if (!$isFinalized): ?>
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
                                    <form method="POST" action="<?= admin_url('application-eligibility.php') ?>" class="inline" data-confirm="Delete this record?">
                                        <?= admin_csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $el->id ?>">
                                        <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                                        <button type="submit" class="ml-2 font-bold text-rose-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php endif; ?>
                <?php if (!$isFinalized): ?>
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
                <?php endif; ?>
                </div>
            </section>

            <!-- Submitted Documents -->
            <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <?= admin_section_header('Submitted Documents', 'paperclip', null, empty($documents) ? '' : '<button type="button" id="download-all-docs" title="If your browser blocks some files, click the download icon near the address bar and choose Allow — all documents will then download together." class="inline-flex items-center gap-1.5 rounded-lg bg-white/15 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-white/25"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>Download All</button>') ?>
                <div class="p-6">
                <?php if (!empty($documents) && count($documents) > 1): ?>
                    <p id="download-all-hint" class="mb-3 hidden rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-700">If your browser blocked some of the files, click the download icon near the address bar and choose "Allow" — all documents will then download together.</p>
                <?php endif; ?>
                <?php if (empty($documents)): ?>
                    <p class="mb-4 text-sm text-slate-500">No records submitted.</p>
                <?php else: ?>
                    <div class="mb-4 overflow-x-auto"><table class="w-full text-sm">
                        <thead><tr>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Document Type</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">File</th>
                            <?php if (!$isFinalized): ?><th class="border-b-2 border-slate-300 px-3 py-2 text-center font-bold">Actions</th><?php endif; ?>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td class="border-b border-slate-200 px-3 py-2"><span class="pill bg-admin-indigoLight text-admin-indigo"><?= admin_e($doc->type) ?></span></td>
                                <td class="border-b border-slate-200 px-3 py-2"><button type="button" class="doc-view-trigger text-admin-indigo underline" data-url="<?= admin_e(admin_url('document.php?path='.urlencode($doc->file_path))) ?>" data-name="<?= admin_e(basename($doc->file_path)) ?>"><?= admin_e(basename($doc->file_path)) ?></button></td>
                                <?php if (!$isFinalized): ?>
                                <td class="border-b border-slate-200 px-3 py-2 text-center">
                                    <form method="POST" action="<?= admin_url('application-documents.php') ?>" class="inline" data-confirm="Delete this document?">
                                        <?= admin_csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $doc->id ?>">
                                        <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                                        <button type="submit" class="font-bold text-rose-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php endif; ?>
                <?php if (!$isFinalized): ?>
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
                <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
<?php if (!$embed): ?>
</div>
<?php endif; ?>

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
