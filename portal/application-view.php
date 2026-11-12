<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

require __DIR__.'/_documents.php';

portal_require_login();
$user = portal_user();

$applicationId = (int) ($_GET['application'] ?? 0);
if (!$applicationId) {
    http_response_code(404);
    exit;
}

$pdo = portal_pdo();

$stmt = $pdo->prepare('SELECT * FROM applications WHERE id = ? AND applicant_id = ?');
$stmt->execute([$applicationId, $user->id]);
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
$documentsByType = [];
foreach ($documents as $doc) {
    $documentsByType[$doc->type] = $doc;
}

function portal_view_section(string $title): string
{
    return '<div class="border-b border-slate-100 bg-slate-50 px-6 py-3"><h3 class="text-sm font-black uppercase tracking-wide text-government-navy">'.portal_e($title).'</h3></div>';
}

$pageTitle = ($job->title ?? 'Application').' — View | DepEd Recruitment Portal';
require __DIR__.'/_layout_head.php';
?>

<?php $activePage = 'dashboard'; require __DIR__.'/_layout_topbar.php'; ?>

<div class="lg:flex lg:flex-1">
    <?php require __DIR__.'/_layout_sidebar.php'; ?>

    <div class="min-w-0 flex-1">
        <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <a href="<?= portal_url('dashboard.php') ?>" class="mb-4 inline-flex items-center gap-1.5 text-sm font-bold text-slate-500 hover:text-government-navy">&larr; Back to My Applications</a>

            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-2xl font-black text-government-dark"><?= portal_e($job->title ?? 'Application Details') ?></h2>
                <span class="inline-flex w-fit items-center rounded-full bg-slate-100 px-4 py-1.5 text-xs font-bold uppercase text-slate-600 ring-1 ring-slate-200">View only</span>
            </div>

            <p class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-700">This application can no longer be edited. You can still review everything you submitted below.</p>

            <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <?= portal_view_section('Application Info') ?>
                <div class="grid gap-4 p-6 sm:grid-cols-2">
                    <div><p class="text-xs font-bold uppercase text-slate-500">Job Position</p><p class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= portal_e($job->title ?? '—') ?></p></div>
                    <div><p class="text-xs font-bold uppercase text-slate-500">Current Status</p><p class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= portal_e(ucfirst($application->status)) ?></p></div>
                    <div><p class="text-xs font-bold uppercase text-slate-500">Control Number</p><p class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= portal_e($controlNumberRow->control_number ?? 'Not yet assigned') ?></p></div>
                    <div><p class="text-xs font-bold uppercase text-slate-500">Applied On</p><p class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= portal_e(date('F d, Y', strtotime($application->created_at))) ?></p></div>
                </div>
            </section>

            <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <?= portal_view_section('Applicant Profile') ?>
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
                        <div class="rounded-lg border border-slate-300 p-2.5"><p class="text-sm font-bold text-slate-700"><?= portal_e($label) ?></p><span class="text-slate-600"><?= portal_e($value ?: '—') ?></span></div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <?= portal_view_section('Educational Background') ?>
                <div class="p-6">
                <?php if (empty($educations)): ?>
                    <p class="text-sm text-slate-500">No records submitted.</p>
                <?php else: ?>
                    <div class="overflow-x-auto"><table class="w-full text-sm">
                        <thead><tr>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Level</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">School / Institution</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Degree / Course</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Year Graduated</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($educations as $e): ?>
                            <tr>
                                <td class="border-b border-slate-200 px-3 py-2"><?= portal_e($e->level === "Other's" && $e->level_specify ? $e->level.' - '.$e->level_specify : $e->level) ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= portal_e($e->school ?: '—') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= portal_e($e->degree ?: '—') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= portal_e($e->year_graduated ?: '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php endif; ?>
                </div>
            </section>

            <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <?= portal_view_section('Work Experience') ?>
                <div class="p-6">
                <?php if (empty($experiences)): ?>
                    <p class="text-sm text-slate-500">No records submitted.</p>
                <?php else: ?>
                    <div class="overflow-x-auto"><table class="w-full text-sm">
                        <thead><tr>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Job Title</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Company</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">First Day</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Last Day</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Details</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($experiences as $x): ?>
                            <tr>
                                <td class="border-b border-slate-200 px-3 py-2"><?= portal_e($x->title ?: '—') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= portal_e($x->company ?: '—') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= portal_e($x->first_day ?: 'Not provided') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= portal_e($x->last_day ?: 'Not provided') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= portal_e($x->details ?: 'Not provided') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php endif; ?>
                </div>
            </section>

            <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <?= portal_view_section('Trainings & Seminars') ?>
                <div class="p-6">
                <?php if (empty($trainings)): ?>
                    <p class="text-sm text-slate-500">No records submitted.</p>
                <?php else: ?>
                    <div class="overflow-x-auto"><table class="w-full text-sm">
                        <thead><tr>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Training Title</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Hours</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Start</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">End</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($trainings as $t): ?>
                            <tr>
                                <td class="border-b border-slate-200 px-3 py-2"><?= portal_e($t->title ?: '—') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= portal_e(($t->hours ?: '0').' hrs') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= $t->training_date ? portal_e(date('F Y', strtotime($t->training_date))) : 'Not provided' ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= $t->training_end_date ? portal_e(date('F Y', strtotime($t->training_end_date))) : 'Not provided' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php endif; ?>
                </div>
            </section>

            <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <?= portal_view_section('Eligibilities / Licenses') ?>
                <div class="p-6">
                <?php if (empty($eligibilities)): ?>
                    <p class="text-sm text-slate-500">No records submitted.</p>
                <?php else: ?>
                    <div class="overflow-x-auto"><table class="w-full text-sm">
                        <thead><tr>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">License / Eligibility</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Rating</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Date Issued</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Valid Until</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($eligibilities as $el): $neverExpires = !empty($el->never_expires) && $el->never_expires != '0'; ?>
                            <tr>
                                <td class="border-b border-slate-200 px-3 py-2"><?= portal_e(in_array($el->license_name, ['RA1080', "Other's"], true) && $el->license_specify ? $el->license_name.' - '.$el->license_specify : $el->license_name) ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= portal_e($el->rating ?: '—') ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= $el->date_issued ? portal_e(date('M d, Y', strtotime($el->date_issued))) : '—' ?></td>
                                <td class="border-b border-slate-200 px-3 py-2"><?= $neverExpires ? 'Never Expires' : ($el->valid_until ? portal_e(date('M d, Y', strtotime($el->valid_until))) : '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php endif; ?>
                </div>
            </section>

            <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <?= portal_view_section('Submitted Documents') ?>
                <div class="p-6">
                    <div class="overflow-x-auto"><table class="w-full text-sm">
                        <thead><tr>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">Document Type</th>
                            <th class="border-b-2 border-slate-300 px-3 py-2 text-left font-bold">File</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach (DOCUMENT_FIELDS as $field => $label): $doc = $documentsByType[$field] ?? null; ?>
                            <tr>
                                <td class="border-b border-slate-200 px-3 py-2"><span class="inline-flex rounded-md bg-green-50 px-2.5 py-1 text-xs font-bold text-government-navy"><?= portal_e($label) ?></span></td>
                                <?php if ($doc): ?>
                                <td class="border-b border-slate-200 px-3 py-2"><a href="<?= portal_e(portal_url('document.php?path='.urlencode($doc->file_path))) ?>" target="_blank" rel="noopener" class="font-bold text-government-navy underline"><?= portal_e(basename($doc->file_path)) ?></a></td>
                                <?php else: ?>
                                <td class="border-b border-slate-200 px-3 py-2 text-slate-400 italic">No <?= portal_e($label) ?> attached</td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        <?php foreach ($documents as $doc): if (array_key_exists($doc->type, DOCUMENT_FIELDS)) { continue; } ?>
                            <tr>
                                <td class="border-b border-slate-200 px-3 py-2"><span class="inline-flex rounded-md bg-green-50 px-2.5 py-1 text-xs font-bold text-government-navy"><?= portal_e($doc->type) ?></span></td>
                                <td class="border-b border-slate-200 px-3 py-2"><a href="<?= portal_e(portal_url('document.php?path='.urlencode($doc->file_path))) ?>" target="_blank" rel="noopener" class="font-bold text-government-navy underline"><?= portal_e(basename($doc->file_path)) ?></a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                </div>
            </section>
        </main>
    </div>
</div>

<?php require __DIR__.'/_layout_footer.php'; ?>
