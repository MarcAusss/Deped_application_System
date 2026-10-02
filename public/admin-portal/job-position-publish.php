<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

admin_require_login();
$pdo = portal_pdo();

$positionId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$positionId) {
    http_response_code(404);
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM job_positions WHERE id = ?');
$stmt->execute([$positionId]);
$position = $stmt->fetch();
if (!$position) {
    http_response_code(404);
    exit;
}

$errors = new Errors();
$returnTo = $_GET['return'] ?? 'job-postings';
$returnUrl = admin_url($returnTo === 'closed-job-postings' ? 'closed-job-postings.php' : 'job-postings.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $title = trim($_POST['title'] ?? '');
    $abbreviation = trim($_POST['abbreviation'] ?? '');
    $slots = trim($_POST['slots'] ?? '1');
    $description = trim($_POST['description'] ?? '');
    $postedAt = trim($_POST['posted_at'] ?? '');
    $until = trim($_POST['until'] ?? '');
    $untilTimeRaw = trim($_POST['until_time'] ?? '');
    $salaryGrade = trim($_POST['salary_grade'] ?? '');
    $monthlySalary = trim($_POST['monthly_salary'] ?? '');
    $educationRequirement = trim($_POST['education_requirement'] ?? '');
    $trainingRequirement = trim($_POST['training_requirement'] ?? '');
    $minTrainingHours = trim($_POST['min_training_hours'] ?? '');
    $experienceRequirement = trim($_POST['experience_requirement'] ?? '');
    $minExperienceYears = trim($_POST['min_experience_years'] ?? '');
    $eligibilityRequirement = trim($_POST['eligibility_requirement'] ?? '');

    if (!v_present($title)) {
        $errors->add('title', 'The title field is required.');
    }
    if (!v_present($description)) {
        $errors->add('description', 'The description field is required.');
    }

    $untilTimeNormalized = null;
    if ($untilTimeRaw !== '') {
        $normalized = admin_normalize_closing_time($untilTimeRaw);
        $ts = strtotime($normalized);
        if ($ts === false) {
            $errors->add('until_time', 'Enter a valid time, e.g. 5:00 PM, 12 midnight, or 12 noon.');
        } else {
            $untilTimeNormalized = date('H:i:s', $ts);
        }
    }

    if (!$errors->any()) {
        // "Manage/Publish" semantics: force is_open=1, stamp posted_at if
        // still blank, generate jp_number only the first time this position
        // is ever posted — identical logic used by both Job Postings'
        // "Manage" action and Closed Postings' "Repost" action.
        $finalPostedAt = $postedAt ?: date('Y-m-d');
        $jpNumber = $position->jp_number ?: admin_generate_jp_number();

        $pdo->prepare(
            'UPDATE job_positions SET
                title = ?, abbreviation = ?, slots = ?, description = ?, posted_at = ?, `until` = ?, until_time = ?,
                salary_grade = ?, monthly_salary = ?, education_requirement = ?, training_requirement = ?, min_training_hours = ?,
                experience_requirement = ?, min_experience_years = ?, eligibility_requirement = ?,
                is_open = 1, jp_number = ?, updated_at = NOW()
             WHERE id = ?'
        )->execute([
            $title, $abbreviation ?: null, (int) $slots, $description,
            $finalPostedAt, $until ?: null, $untilTimeNormalized,
            $salaryGrade ?: null, $monthlySalary !== '' ? $monthlySalary : null,
            $educationRequirement ?: null, $trainingRequirement ?: null, $minTrainingHours !== '' ? (int) $minTrainingHours : null,
            $experienceRequirement ?: null, $minExperienceYears !== '' ? $minExperienceYears : null,
            $eligibilityRequirement ?: null,
            $jpNumber,
            $positionId,
        ]);

        flash_set('success', 'Job position posted.');
        header('Location: '.$returnUrl);
        exit;
    }

    flash_set('_errors', $errors->all());
    set_old_input($_POST);
    header('Location: '.admin_url('job-position-publish.php?id='.$positionId.'&return='.$returnTo));
    exit;
}

foreach (validation_errors() as $field => $message) {
    $errors->add($field, $message);
}
$old = fn (string $field, $default = '') => admin_old($field, $default);

$pageTitle = 'Publish Job Position | Administration';
require __DIR__.'/_layout_head.php';
?>

<?php $activePage = 'job-postings'; require __DIR__.'/_layout_topbar.php'; ?>

<div class="lg:flex lg:flex-1">
    <?php require __DIR__.'/_layout_sidebar.php'; ?>

    <div class="min-w-0 flex-1">
        <main class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
            <a href="<?= $returnUrl ?>" class="mb-4 inline-flex items-center gap-1.5 text-sm font-bold text-slate-500 hover:text-admin-indigo">&larr; Back</a>

            <h2 class="mb-2 text-2xl font-black text-admin-indigoDark">Publish / Repost: <?= admin_e($position->title) ?></h2>
            <p class="mb-6 text-sm text-slate-600">Review and confirm the details below, then post this position so applicants can see and apply to it.</p>

            <?php $submitLabel = 'Post This Job Position'; require __DIR__.'/_job_position_form.php'; ?>
        </main>
    </div>
</div>

<?php require __DIR__.'/_layout_footer.php'; ?>
