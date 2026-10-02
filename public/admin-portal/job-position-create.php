<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

admin_require_login();
$pdo = portal_pdo();

$errors = new Errors();

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
    if ($slots === '' || (int) $slots < 1) {
        $errors->add('slots', 'No. of Vacancies must be at least 1.');
    }
    if ($until !== '' && $postedAt !== '' && $until < $postedAt) {
        $errors->add('until', 'Until must be on or after Posted.');
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

    $attachmentPaths = [];
    $cscPaths = [];
    try {
        if (!empty($_FILES['attachment_paths']['name'][0])) {
            $attachmentPaths = admin_store_uploads_preserve_names($_FILES['attachment_paths'], 'job-positions', ['pdf']);
        }
        if (!empty($_FILES['csc_publication_paths']['name'][0])) {
            $cscPaths = admin_store_uploads_preserve_names($_FILES['csc_publication_paths'], 'job-positions', ['pdf']);
        }
    } catch (RuntimeException $e) {
        $errors->add('attachment_paths', $e->getMessage());
    }

    if (!$errors->any()) {
        $pdo->prepare(
            'INSERT INTO job_positions
                (title, abbreviation, slots, description, posted_at, `until`, until_time, salary_grade, monthly_salary,
                 education_requirement, training_requirement, min_training_hours, experience_requirement, min_experience_years,
                 eligibility_requirement, is_open, attachment_paths, csc_publication_paths, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, NOW(), NOW())'
        )->execute([
            $title, $abbreviation ?: null, (int) $slots, $description,
            $postedAt ?: null, $until ?: null, $untilTimeNormalized,
            $salaryGrade ?: null, $monthlySalary !== '' ? $monthlySalary : null,
            $educationRequirement ?: null, $trainingRequirement ?: null, $minTrainingHours !== '' ? (int) $minTrainingHours : null,
            $experienceRequirement ?: null, $minExperienceYears !== '' ? $minExperienceYears : null,
            $eligibilityRequirement ?: null,
            json_encode($attachmentPaths), json_encode($cscPaths),
        ]);

        flash_set('success', 'Job position created.');
        header('Location: '.admin_url('job-positions.php'));
        exit;
    }

    flash_set('_errors', $errors->all());
    set_old_input($_POST);
    header('Location: '.admin_url('job-position-create.php'));
    exit;
}

foreach (validation_errors() as $field => $message) {
    $errors->add($field, $message);
}
$old = fn (string $field, $default = '') => admin_old($field, $default);

$pageTitle = 'New Job Position | Administration';
require __DIR__.'/_layout_head.php';
?>

<?php $activePage = 'job-positions'; require __DIR__.'/_layout_topbar.php'; ?>

<div class="lg:flex lg:flex-1">
    <?php require __DIR__.'/_layout_sidebar.php'; ?>

    <div class="min-w-0 flex-1">
        <main class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
            <a href="<?= admin_url('job-positions.php') ?>" class="mb-4 inline-flex items-center gap-1.5 text-sm font-bold text-slate-500 hover:text-admin-indigo">&larr; Back to Job Positions</a>

            <h2 class="mb-6 text-2xl font-black text-admin-indigoDark">New Job Position</h2>

            <?php require __DIR__.'/_job_position_form.php'; ?>
        </main>
    </div>
</div>

<?php require __DIR__.'/_layout_footer.php'; ?>
