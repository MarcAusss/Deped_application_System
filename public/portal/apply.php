<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

portal_require_login();
$user = portal_user();

const DOCUMENT_FIELDS = [
    'letter_of_intent' => 'Letter of Intent',
    'tor_diploma' => 'TOR / Diploma',
    'prc_license' => 'PRC License',
    'eligibility_file' => 'Eligibility Document',
    'training_certificates' => 'Training Certificates',
    'employment_records' => 'Employment Records',
    'latest_appointment' => 'Latest Appointment',
    'performance_rating' => 'Performance Rating',
    'cav' => 'CAV',
    'movs' => 'Other MOVs/Documents',
];

function job_deadline_passed(?string $until, ?string $untilTime): bool
{
    if (!$until) {
        return false;
    }

    return time() > strtotime($until.' '.($untilTime ?: '23:59:59'));
}

function job_editing_window_closed(?object $job): bool
{
    return !$job || !((bool) $job->is_open) || job_deadline_passed($job->until, $job->until_time);
}

function has_entered_data(array $row): bool
{
    foreach ($row as $key => $value) {
        if ($key === '_csrf_token') {
            continue;
        }
        if ($value !== null && $value !== '' && $value !== false) {
            return true;
        }
    }

    return false;
}

function is_pdf_file(string $tmpPath): bool
{
    $handle = @fopen($tmpPath, 'rb');
    if (!$handle) {
        return false;
    }
    $header = fread($handle, 5);
    fclose($handle);

    return $header === '%PDF-';
}

function store_uploaded_document(array $file): string
{
    $dir = PORTAL_ROOT.'/storage/app/public/documents';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $filename = bin2hex(random_bytes(20)).'.pdf';
    move_uploaded_file($file['tmp_name'], $dir.'/'.$filename);

    return 'documents/'.$filename;
}

function job_paths(?string $json): array
{
    if (!$json) {
        return [];
    }
    $decoded = json_decode($json, true);

    return is_array($decoded) ? $decoded : [];
}

// ---------------------------------------------------------------------
// Resolve mode: editing an existing (still-pending) application, or
// creating a new one against an open job.
// ---------------------------------------------------------------------

$applicationId = (int) ($_GET['application'] ?? $_POST['application_id'] ?? 0);
$jobId = (int) ($_GET['job'] ?? $_POST['job_id'] ?? 0);

$application = null;
$job = null;
$existingData = null; // profile/education/experience/training/eligibility/documents, when editing

if ($applicationId) {
    $stmt = portal_pdo()->prepare('SELECT * FROM applications WHERE id = ?');
    $stmt->execute([$applicationId]);
    $application = $stmt->fetch();

    if (!$application || (int) $application->applicant_id !== (int) $user->id) {
        http_response_code(404);
        exit;
    }

    if ($application->status !== 'pending') {
        flash_set('error', 'This application can no longer be edited since it has already been evaluated.');
        header('Location: '.portal_url('dashboard.php'));
        exit;
    }

    $stmt = portal_pdo()->prepare('SELECT * FROM job_positions WHERE id = ?');
    $stmt->execute([$application->job_position_id]);
    $job = $stmt->fetch();

    if (job_editing_window_closed($job)) {
        flash_set('error', 'This application can no longer be edited since the position has closed.');
        header('Location: '.portal_url('dashboard.php'));
        exit;
    }

    $stmt = portal_pdo()->prepare('SELECT * FROM applicant_profiles WHERE application_id = ?');
    $stmt->execute([$application->id]);
    $profile = $stmt->fetch();

    $fetchAll = function (string $table) use ($application) {
        $stmt = portal_pdo()->prepare("SELECT * FROM {$table} WHERE application_id = ? ORDER BY id");
        $stmt->execute([$application->id]);

        return $stmt->fetchAll();
    };

    $existingData = [
        'profile' => $profile,
        'educations' => $fetchAll('applicant_educations'),
        'experiences' => $fetchAll('applicant_experiences'),
        'trainings' => $fetchAll('applicant_trainings'),
        'eligibilities' => $fetchAll('applicant_eligibilities'),
        'documents' => $fetchAll('application_documents'),
    ];
} elseif ($jobId) {
    $stmt = portal_pdo()->prepare('SELECT * FROM job_positions WHERE id = ?');
    $stmt->execute([$jobId]);
    $job = $stmt->fetch();

    if (!$job) {
        http_response_code(404);
        exit;
    }

    if (!$job->is_open || job_deadline_passed($job->until, $job->until_time)) {
        http_response_code(403);
        echo 'This job position is currently closed.';
        exit;
    }
} else {
    http_response_code(404);
    exit;
}

$isEdit = $application !== null;

// ---------------------------------------------------------------------
// POST: validate + save
// ---------------------------------------------------------------------

$errors = new Errors();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $birthDate = trim($_POST['birth_date'] ?? '');
    $sex = trim($_POST['sex'] ?? '');
    $civilStatus = trim($_POST['civil_status'] ?? '');
    $religion = trim($_POST['religion'] ?? '');
    $disability = trim($_POST['disability'] ?? '');
    $ethnicGroup = trim($_POST['ethnic_group'] ?? '');

    if (!v_present($fullName)) { $errors->add('full_name', 'The full name field is required.'); }
    elseif (!v_max($fullName, 255)) { $errors->add('full_name', 'The full name field must not be greater than 255 characters.'); }

    if (!v_present($email)) { $errors->add('email', 'The email field is required.'); }
    elseif (!v_email($email)) { $errors->add('email', 'The email field must be a valid email address.'); }

    if ($birthDate !== '' && (!v_date($birthDate) || !v_date_before_or_equal_today($birthDate))) {
        $errors->add('birth_date', 'The birth date field must be a date before or equal to today.');
    }

    if (!v_present($religion)) { $errors->add('religion', 'The religion field is required.'); }

    $educationRows = $_POST['education'] ?? [];
    $experienceRows = $_POST['experience'] ?? [];
    $trainingRows = $_POST['training'] ?? [];
    $eligibilityRows = $_POST['eligibility'] ?? [];

    foreach ($trainingRows as $i => $row) {
        foreach (['training_date', 'training_end_date'] as $dateField) {
            $value = $row[$dateField] ?? '';
            if ($value !== '' && !v_date_format_ym($value)) {
                $errors->add("training.{$i}.{$dateField}", 'The training date must be a valid month/year not in the future.');
            }
        }
    }

    foreach (DOCUMENT_FIELDS as $field => $label) {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            $errors->add($field, "The {$label} failed to upload. Please try again.");
            continue;
        }
        if ($_FILES[$field]['size'] > 10240 * 1024) {
            $errors->add($field, "The {$label} must not be greater than 10 MB.");
            continue;
        }
        if (!is_pdf_file($_FILES[$field]['tmp_name'])) {
            $errors->add($field, "The {$label} must be a PDF file.");
        }
    }

    if (!$errors->any()) {
        try {
            portal_pdo()->beginTransaction();

            $profileData = [
                'full_name' => $fullName, 'email' => $email, 'phone' => $phone ?: null, 'address' => $address ?: null,
                'birth_date' => $birthDate ?: null, 'sex' => $sex ?: null, 'civil_status' => $civilStatus ?: null,
                'religion' => $religion, 'disability' => $disability ?: null, 'ethnic_group' => $ethnicGroup ?: null,
            ];

            if ($isEdit) {
                $applicationRowId = $application->id;

                $set = implode(', ', array_map(fn ($c) => "$c = :$c", array_keys($profileData)));
                $stmt = portal_pdo()->prepare("UPDATE applicant_profiles SET {$set}, updated_at = :updated_at WHERE application_id = :application_id");
                $stmt->execute($profileData + ['updated_at' => date('Y-m-d H:i:s'), 'application_id' => $applicationRowId]);

                foreach (['applicant_educations', 'applicant_experiences', 'applicant_trainings', 'applicant_eligibilities'] as $table) {
                    portal_pdo()->prepare("DELETE FROM {$table} WHERE application_id = ?")->execute([$applicationRowId]);
                }
            } else {
                $applicationRowId = portal_insert('applications', [
                    'job_position_id' => $job->id,
                    'applicant_id' => $user->id,
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                $tag = $job->abbreviation ?: $job->title;
                do {
                    $controlNumber = 'Alb-'.$tag.'-'.random_int(1000, 9999).'-'.date('Y');
                    $check = portal_pdo()->prepare('SELECT id FROM application_control_numbers WHERE control_number = ?');
                    $check->execute([$controlNumber]);
                } while ($check->fetch());

                portal_insert('application_control_numbers', [
                    'application_id' => $applicationRowId,
                    'control_number' => $controlNumber,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                portal_insert('applicant_profiles', $profileData + [
                    'application_id' => $applicationRowId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $now = date('Y-m-d H:i:s');

            foreach ($educationRows as $row) {
                if (!has_entered_data($row)) {
                    continue;
                }
                $level = $row['level'] ?? null;
                portal_insert('applicant_educations', [
                    'application_id' => $applicationRowId,
                    'level' => $level ?: '',
                    'level_specify' => $level === "Other's" ? ($row['level_specify'] ?? null) : null,
                    'school' => $row['school'] ?? null,
                    'degree' => $row['degree'] ?? null,
                    'year_graduated' => $row['year_graduated'] ?? null,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            foreach ($experienceRows as $row) {
                if (!has_entered_data($row)) {
                    continue;
                }
                portal_insert('applicant_experiences', [
                    'application_id' => $applicationRowId,
                    'title' => $row['title'] ?? '',
                    'company' => $row['company'] ?? null,
                    'first_day' => $row['first_day'] ?? null,
                    'last_day' => $row['last_day'] ?? null,
                    'years_months' => $row['years_months'] ?? null,
                    'details' => $row['details'] ?? null,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            foreach ($trainingRows as $row) {
                if (!has_entered_data($row)) {
                    continue;
                }
                portal_insert('applicant_trainings', [
                    'application_id' => $applicationRowId,
                    'title' => $row['title'] ?? '',
                    'hours' => ($row['hours'] ?? '') !== '' ? (int) $row['hours'] : null,
                    'training_date' => !empty($row['training_date']) ? $row['training_date'].'-01' : null,
                    'training_end_date' => !empty($row['training_end_date']) ? $row['training_end_date'].'-01' : null,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            foreach ($eligibilityRows as $row) {
                if (!has_entered_data($row)) {
                    continue;
                }
                $licenseName = $row['license_name'] ?? null;
                $neverExpires = !empty($row['never_expires']);
                portal_insert('applicant_eligibilities', [
                    'application_id' => $applicationRowId,
                    'license_name' => $licenseName,
                    'license_specify' => in_array($licenseName, ['RA1080', "Other's"], true) ? ($row['license_specify'] ?? null) : null,
                    'rating' => $row['rating'] ?? null,
                    'date_issued' => $row['date_issued'] ?? null,
                    'valid_until' => $neverExpires ? null : ($row['valid_until'] ?? null),
                    'never_expires' => $neverExpires ? 1 : 0,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            foreach (DOCUMENT_FIELDS as $field => $label) {
                if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
                    continue;
                }

                if ($isEdit) {
                    $stmt = portal_pdo()->prepare('SELECT * FROM application_documents WHERE application_id = ? AND type = ?');
                    $stmt->execute([$applicationRowId, $field]);
                    $existing = $stmt->fetch();

                    if ($existing) {
                        $oldPath = PORTAL_ROOT.'/storage/app/public/'.$existing->file_path;
                        if (is_file($oldPath)) {
                            @unlink($oldPath);
                        }
                        portal_pdo()->prepare('DELETE FROM application_documents WHERE id = ?')->execute([$existing->id]);
                    }
                }

                $path = store_uploaded_document($_FILES[$field]);

                portal_insert('application_documents', [
                    'application_id' => $applicationRowId,
                    'type' => $field,
                    'file_path' => $path,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            portal_pdo()->commit();
        } catch (\Throwable $e) {
            if (portal_pdo()->inTransaction()) {
                portal_pdo()->rollBack();
            }
            error_log('[portal] apply.php save failed: '.$e->getMessage());

            flash_set('error', 'The application could not be '.($isEdit ? 'updated' : 'submitted').'. Please try again.');
            set_old_input($_POST);
            header('Location: '.portal_url('apply.php').'?'.($isEdit ? 'application='.$applicationId : 'job='.$jobId));
            exit;
        }

        if ($isEdit) {
            flash_set('success', 'Your application was updated successfully.');
            header('Location: '.portal_url('dashboard.php'));
            exit;
        }

        try {
            send_application_submitted($email, $fullName, $job->title, date('F d, Y - h:i A'));
        } catch (\Throwable $e) {
            error_log('[portal] confirmation email failed: '.$e->getMessage());
        }

        flash_set('success', 'Your application was submitted successfully.');
        header('Location: '.portal_url('jobs.php'));
        exit;
    }

    flash_set('_errors', $errors->all());
    set_old_input($_POST);
    header('Location: '.portal_url('apply.php').'?'.($isEdit ? 'application='.$applicationId : 'job='.$jobId));
    exit;
}

// ---------------------------------------------------------------------
// GET: render the form
// ---------------------------------------------------------------------

$errors = new Errors();
foreach (validation_errors() as $field => $message) {
    $errors->add($field, $message);
}

$profile = $existingData['profile'] ?? null;
$val = fn (string $field, $fallback = '') => portal_old($field, $fallback ?? '');

$educationSource = old_array('education') ?? array_map(fn ($e) => (array) $e, $existingData['educations'] ?? []);
$experienceSource = old_array('experience') ?? array_map(fn ($e) => (array) $e, $existingData['experiences'] ?? []);
$trainingSource = old_array('training') ?? array_map(function ($t) {
    $t = (array) $t;
    if (!empty($t['training_date'])) { $t['training_date'] = substr($t['training_date'], 0, 7); }
    if (!empty($t['training_end_date'])) { $t['training_end_date'] = substr($t['training_end_date'], 0, 7); }
    return $t;
}, $existingData['trainings'] ?? []);
$eligibilitySource = old_array('eligibility') ?? array_map(fn ($e) => (array) $e, $existingData['eligibilities'] ?? []);

$existingDocuments = [];
foreach ($existingData['documents'] ?? [] as $doc) {
    $existingDocuments[$doc->type] = $doc;
}

$attachments = job_paths($job->attachment_paths);
$cscPaths = job_paths($job->csc_publication_paths);

$pageTitle = ($isEdit ? 'Edit Application' : 'Apply').' for '.$job->title;
require __DIR__.'/_layout_head.php';
?>

<?php $activePage = 'jobs'; require __DIR__.'/_layout_topbar.php'; ?>

<section class="relative overflow-hidden bg-gradient-to-r from-government-dark to-government-blue text-white">
    <div class="mx-auto flex max-w-5xl flex-col items-center justify-center gap-2 px-4 py-12 sm:flex-row sm:px-6 lg:px-8">
        <img src="/images/depedalbay.png" alt="DepEd Division of Albay" class="h-[8.75rem] w-[8.75rem] shrink-0 object-contain">
        <div class="text-justify">
            <p class="text-base font-bold uppercase tracking-widest text-white">Welcome!</p>
            <h2 class="mt-1 font-black">
                <span class="text-2xl sm:text-3xl">SDO ALBAY CARES</span><br>
                <span class="text-lg sm:text-xl">(Career Application & <br>Recruitment for Education Services)</span>
            </h2>
            <p class="mt-1 text-lg text-blue-100">Applying for: <span class="font-black text-white"><?= portal_e($job->title) ?></span></p>
        </div>
    </div>

    <?php if ($attachments || $cscPaths): ?>
        <div class="mx-4 mb-4 flex flex-col items-start gap-2 sm:mx-6 sm:mb-6 lg:absolute lg:bottom-6 lg:left-8 lg:mx-0 lg:mb-0">
            <div class="flex flex-col items-start gap-3 sm:flex-row sm:flex-wrap sm:gap-6">
                <?php foreach ($attachments as $index => $path): ?>
                    <a href="<?= portal_url('document.php?path='.urlencode($path)) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-lg border border-white/30 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-white/10">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9.75v6.75m0 0-3-3m3 3 3-3m-8.25 6a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" /></svg>
                        D.M Notice<?= count($attachments) > 1 ? ' '.($index + 1) : '' ?>
                    </a>
                <?php endforeach; ?>
                <?php foreach ($cscPaths as $index => $path): ?>
                    <a href="<?= portal_url('document.php?path='.urlencode($path)) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-lg border border-white/30 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-white/10">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9.75v6.75m0 0-3-3m3 3 3-3m-8.25 6a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" /></svg>
                        CSC Publication of Vacancy<?= count($cscPaths) > 1 ? ' '.($index + 1) : '' ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <p class="whitespace-nowrap text-xs text-blue-100">Important: Review the D.M. Notice and CSC Publication of Vacancy for full qualifications, requirements, and deadlines.</p>
        </div>
    <?php endif; ?>

    <?php if ($job->posted_at || $job->until): ?>
        <div class="mx-4 mb-4 flex flex-col items-end gap-1 sm:mx-6 sm:mb-6 lg:absolute lg:bottom-6 lg:right-8 lg:mx-0 lg:mb-0">
            <div class="flex flex-col items-end gap-3 sm:flex-row sm:gap-6">
                <?php if ($job->posted_at): ?><div class="text-right"><span class="text-xs font-bold uppercase tracking-wider text-yellow-300">Posted:</span> <span class="font-bold text-white"><?= portal_e(date('F d, Y', strtotime($job->posted_at))) ?></span></div><?php endif; ?>
                <?php if ($job->until): ?><div class="text-right"><span class="text-xs font-bold uppercase tracking-wider text-yellow-300">Until:</span> <span class="font-bold text-white"><?= portal_e(date('F d, Y', strtotime($job->until))) ?><?= $job->until_time ? ' '.portal_e(date('g:i A', strtotime($job->until_time))) : '' ?></span></div><?php endif; ?>
            </div>
            <div class="text-right"><span class="text-xs font-bold uppercase tracking-wider text-yellow-300">No. of Vacancies:</span> <span class="font-bold text-white"><?= (int) $job->slots ?></span></div>
        </div>
    <?php endif; ?>
</section>

<div class="lg:flex lg:flex-1">
    <?php $hideLogout = true; require __DIR__.'/_layout_sidebar.php'; ?>

    <div class="min-w-0 flex-1">
        <main class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">

            <?php if (flash_get('error')): ?>
                <div class="mb-8 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800"><?= portal_e(flash_get('error')) ?></div>
            <?php endif; ?>

            <?php if ($errors->any()): ?>
                <div class="mb-8 rounded-xl border border-red-200 bg-red-50 p-5 text-red-800">
                    <p class="font-bold">Please correct the following errors:</p>
                    <ul class="mt-3 list-inside list-disc space-y-1 text-sm">
                        <?php foreach ($errors->all() as $message): ?><li><?= portal_e($message) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= portal_url('apply.php').'?'.($isEdit ? 'application='.$applicationId : 'job='.$jobId) ?>" enctype="multipart/form-data" class="space-y-8">
                <?= portal_csrf_field() ?>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="mb-7 border-b border-slate-200 pb-5">
                        <p class="text-xs font-bold uppercase tracking-widest text-government-blue">Section 1</p>
                        <h3 class="mt-2 text-2xl font-black text-government-dark">Personal Information</h3>
                        <p class="mt-2 text-sm text-slate-500">Enter your complete and accurate personal details.</p>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">Full Name <span class="text-red-600">*</span></label>
                            <input type="text" name="full_name" value="<?= portal_e($val('full_name', $profile->full_name ?? '')) ?>" placeholder="Last Name|First Name|Middle Name|Name Extension" required class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">Email Address <span class="text-red-600">*</span></label>
                            <input type="email" name="email" value="<?= portal_e($val('email', $profile->email ?? '')) ?>" required class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">Phone Number</label>
                            <input type="text" name="phone_number" value="<?= portal_e($val('phone_number', $profile->phone ?? '')) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">Complete Address</label>
                            <input type="text" name="address" value="<?= portal_e($val('address', $profile->address ?? '')) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">Birth Date</label>
                            <input type="date" name="birth_date" value="<?= portal_e($val('birth_date', $profile->birth_date ?? '')) ?>" max="<?= date('Y-m-d') ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">Sex (at Birth)</label>
                            <?php $selectedSex = $val('sex', $profile->sex ?? ''); ?>
                            <select name="sex" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100">
                                <option value="">Select sex</option>
                                <option value="Male" <?= $selectedSex === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= $selectedSex === 'Female' ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">Civil Status</label>
                            <?php $selectedCivilStatus = $val('civil_status', $profile->civil_status ?? ''); ?>
                            <select name="civil_status" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100">
                                <option value="">Select civil status</option>
                                <?php foreach (['Single', 'Married', 'Widowed', 'Legally Separated', 'Divorced', 'Annulled', 'Other'] as $cs): ?>
                                    <option value="<?= portal_e($cs) ?>" <?= $selectedCivilStatus === $cs ? 'selected' : '' ?>><?= portal_e($cs) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">Religion <span class="text-red-600">*</span></label>
                            <input type="text" name="religion" value="<?= portal_e($val('religion', $profile->religion ?? '')) ?>" required class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">Disability</label>
                            <input type="text" name="disability" value="<?= portal_e($val('disability', $profile->disability ?? '')) ?>" placeholder="Optional" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">Ethnic Group</label>
                            <input type="text" name="ethnic_group" value="<?= portal_e($val('ethnic_group', $profile->ethnic_group ?? '')) ?>" placeholder="Optional" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100">
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="mb-7 flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-center">
                        <div><p class="text-xs font-bold uppercase tracking-widest text-government-blue">Section 2</p><h3 class="mt-2 text-2xl font-black text-government-dark">Educational Background</h3></div>
                        <button type="button" onclick="addEducation()" class="rounded-xl bg-government-navy px-5 py-3 text-sm font-bold text-white transition hover:bg-government-blue">+ Add Education</button>
                    </div>
                    <div id="educationWrapper" class="space-y-5">
                        <?php foreach ($educationSource as $i => $education): $education = (array) $education; ?>
                            <div class="dynamic-entry rounded-xl border border-slate-200 bg-slate-50 p-5">
                                <div class="mb-4 flex items-center justify-between">
                                    <h4 class="font-black text-government-dark">Education Entry</h4>
                                    <button type="button" onclick="removeEntry(this)" class="text-sm font-bold text-red-600 hover:text-red-800">Remove</button>
                                </div>
                                <div class="grid gap-4 md:grid-cols-2">
                                    <select name="education[<?= $i ?>][level]" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100" onchange="toggleEducationSpecify(this)">
                                        <option value="" disabled hidden>Education level</option>
                                        <?php foreach (["Bachelor's Degree", "Master's Degree", "Doctorate Degree", "Other's"] as $level): ?>
                                            <option value="<?= portal_e($level) ?>" <?= ($education['level'] ?? '') === $level ? 'selected' : '' ?>><?= portal_e($level) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="education[<?= $i ?>][level_specify]" data-role="education-specify" placeholder="Please specify" value="<?= portal_e($education['level_specify'] ?? '') ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100 <?= ($education['level'] ?? '') === "Other's" ? '' : 'hidden' ?>">
                                    <input type="text" name="education[<?= $i ?>][school]" placeholder="School" value="<?= portal_e($education['school'] ?? '') ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100">
                                    <input type="text" name="education[<?= $i ?>][degree]" placeholder="Degree or course" value="<?= portal_e($education['degree'] ?? '') ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100">
                                    <input type="text" name="education[<?= $i ?>][year_graduated]" placeholder="Year graduated" value="<?= portal_e($education['year_graduated'] ?? '') ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="mb-7 flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-center">
                        <div><p class="text-xs font-bold uppercase tracking-widest text-government-blue">Section 3</p><h3 class="mt-2 text-2xl font-black text-government-dark">Work Experience</h3></div>
                        <button type="button" onclick="addExperience()" class="rounded-xl bg-government-navy px-5 py-3 text-sm font-bold text-white transition hover:bg-government-blue">+ Add Experience</button>
                    </div>
                    <div id="experienceWrapper" class="space-y-5">
                        <?php foreach ($experienceSource as $i => $experience): $experience = (array) $experience; ?>
                            <div class="dynamic-entry rounded-xl border border-slate-200 bg-slate-50 p-5">
                                <div class="mb-4 flex items-center justify-between">
                                    <h4 class="font-black text-government-dark">Experience Entry</h4>
                                    <button type="button" onclick="removeEntry(this)" class="text-sm font-bold text-red-600 hover:text-red-800">Remove</button>
                                </div>
                                <div class="grid gap-4 md:grid-cols-2">
                                    <input type="text" name="experience[<?= $i ?>][title]" placeholder="Position title" value="<?= portal_e($experience['title'] ?? '') ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100">
                                    <input type="text" name="experience[<?= $i ?>][company]" placeholder="Company or agency" value="<?= portal_e($experience['company'] ?? '') ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100">
                                    <input type="text" name="experience[<?= $i ?>][first_day]" placeholder="First Day of Service (Month and Year)" value="<?= portal_e($experience['first_day'] ?? '') ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100">
                                    <input type="text" name="experience[<?= $i ?>][last_day]" placeholder="Last Day of Service (Month and Year)" value="<?= portal_e($experience['last_day'] ?? '') ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100">
                                    <textarea name="experience[<?= $i ?>][details]" placeholder="Responsibilities or details" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"><?= portal_e($experience['details'] ?? '') ?></textarea>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="mb-7 flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-center">
                        <div><p class="text-xs font-bold uppercase tracking-widest text-government-blue">Section 4</p><h3 class="mt-2 text-2xl font-black text-government-dark">Trainings and Seminars</h3></div>
                        <button type="button" onclick="addTraining()" class="rounded-xl bg-government-navy px-5 py-3 text-sm font-bold text-white transition hover:bg-government-blue">+ Add Training</button>
                    </div>
                    <div id="trainingWrapper" class="space-y-5">
                        <?php foreach ($trainingSource as $i => $training): $training = (array) $training; ?>
                            <div class="dynamic-entry rounded-xl border border-slate-200 bg-slate-50 p-5">
                                <div class="mb-4 flex items-center justify-between">
                                    <h4 class="font-black text-government-dark">Training Entry</h4>
                                    <button type="button" onclick="removeEntry(this)" class="text-sm font-bold text-red-600 hover:text-red-800">Remove</button>
                                </div>
                                <div class="grid gap-4 md:grid-cols-2">
                                    <input type="text" name="training[<?= $i ?>][title]" placeholder="Training or seminar title" value="<?= portal_e($training['title'] ?? '') ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100">
                                    <input type="number" min="0" step="1" name="training[<?= $i ?>][hours]" placeholder="Number of hours" value="<?= portal_e((string) ($training['hours'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100">
                                    <div class="md:col-span-2">
                                        <label class="mb-2 block text-sm font-bold text-government-dark">Start of Training</label>
                                        <input type="month" name="training[<?= $i ?>][training_date]" value="<?= portal_e($training['training_date'] ?? '') ?>" max="<?= date('Y-m') ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100">
                                        <label class="mb-2 mt-3 block text-sm font-bold text-government-dark">End of Training</label>
                                        <input type="month" name="training[<?= $i ?>][training_end_date]" value="<?= portal_e($training['training_end_date'] ?? '') ?>" max="<?= date('Y-m') ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100">
                                        <p class="mt-2 text-xs text-slate-500">Select the month and year when the training or seminar started and ended.</p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="mb-7 flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-center">
                        <div><p class="text-xs font-bold uppercase tracking-widest text-government-blue">Section 5</p><h3 class="mt-2 text-2xl font-black text-government-dark">Eligibility and Licenses</h3></div>
                        <button type="button" onclick="addEligibility()" class="rounded-xl bg-government-navy px-5 py-3 text-sm font-bold text-white transition hover:bg-government-blue">+ Add Eligibility</button>
                    </div>
                    <div id="eligibilityWrapper" class="space-y-5">
                        <?php foreach ($eligibilitySource as $i => $eligibility): $eligibility = (array) $eligibility; $neverExpires = !empty($eligibility['never_expires']) && $eligibility['never_expires'] != '0'; ?>
                            <div class="dynamic-entry rounded-xl border border-slate-200 bg-slate-50 p-5">
                                <div class="mb-4 flex items-center justify-between">
                                    <h4 class="font-black text-government-dark">Eligibility Entry</h4>
                                    <button type="button" onclick="removeEntry(this)" class="text-sm font-bold text-red-600 hover:text-red-800">Remove</button>
                                </div>
                                <div class="grid gap-4 md:grid-cols-2">
                                    <select name="eligibility[<?= $i ?>][license_name]" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100" onchange="toggleEligibilitySpecify(this)">
                                        <option value="" disabled hidden>Eligibility or license name</option>
                                        <?php foreach (['CS Sub-Professional', 'CSC Professional', 'RA1080', "Other's"] as $ln): ?>
                                            <option value="<?= portal_e($ln) ?>" <?= ($eligibility['license_name'] ?? '') === $ln ? 'selected' : '' ?>><?= portal_e($ln) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="eligibility[<?= $i ?>][license_specify]" data-role="eligibility-specify" placeholder="Please specify" value="<?= portal_e($eligibility['license_specify'] ?? '') ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100 <?= in_array($eligibility['license_name'] ?? '', ['RA1080', "Other's"], true) ? '' : 'hidden' ?>">
                                    <input type="text" name="eligibility[<?= $i ?>][rating]" placeholder="Rating" value="<?= portal_e($eligibility['rating'] ?? '') ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100">
                                    <div>
                                        <label class="mb-2 block text-sm font-bold text-government-dark">Date Issued</label>
                                        <input type="date" name="eligibility[<?= $i ?>][date_issued]" value="<?= portal_e($eligibility['date_issued'] ?? '') ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100">
                                    </div>
                                    <div data-role="valid-until-wrapper" class="<?= $neverExpires ? 'hidden' : '' ?>">
                                        <label class="mb-2 block text-sm font-bold text-government-dark">Valid Until</label>
                                        <input type="date" name="eligibility[<?= $i ?>][valid_until]" value="<?= portal_e($eligibility['valid_until'] ?? '') ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100">
                                    </div>
                                    <label class="mt-1 ml-auto flex w-fit items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 md:col-span-2">
                                        <input type="checkbox" name="eligibility[<?= $i ?>][never_expires]" value="1" <?= $neverExpires ? 'checked' : '' ?> class="h-4 w-4 rounded border-slate-300 text-government-navy" onchange="toggleNeverExpires(this)">
                                        <span class="text-xs font-semibold text-slate-700">Never expires</span>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="mb-7 border-b border-slate-200 pb-5">
                        <p class="text-xs font-bold uppercase tracking-widest text-government-blue">Section 6</p>
                        <h3 class="mt-2 text-2xl font-black text-government-dark">Supporting Documents</h3>
                        <p class="mt-2 text-sm text-slate-500">PDF files only. Maximum file size is 10 MB per document.</p>
                    </div>
                    <div class="grid gap-5 md:grid-cols-2">
                        <?php foreach (DOCUMENT_FIELDS as $field => $label): $existingDoc = $existingDocuments[$field] ?? null; ?>
                            <div class="rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 p-5">
                                <label for="<?= $field ?>" class="mb-3 block font-bold text-government-dark"><?= portal_e($label) ?></label>
                                <?php if ($existingDoc): ?>
                                    <p class="mb-3 text-sm text-slate-600">Current file: <a href="<?= portal_url('document.php?path='.urlencode($existingDoc->file_path)) ?>" target="_blank" rel="noopener" class="font-bold text-government-blue hover:underline">View current file</a></p>
                                <?php endif; ?>
                                <input type="file" id="<?= $field ?>" name="<?= $field ?>" accept=".pdf,application/pdf" class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-government-navy file:px-4 file:py-2.5 file:font-bold file:text-white hover:file:bg-government-blue">
                                <?php if ($existingDoc): ?><p class="mt-2 text-xs text-slate-500">Leave blank to keep the currently uploaded file.</p><?php endif; ?>
                                <?php if ($errors->has($field)): ?><p class="mt-2 text-sm font-medium text-red-600"><?= portal_e($errors->first($field)) ?></p><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="rounded-2xl border border-blue-100 bg-government-light p-6">
                    <label class="flex items-start gap-3">
                        <input type="checkbox" required class="mt-1 h-5 w-5 rounded border-slate-300 text-government-navy focus:ring-government-blue">
                        <span class="text-sm leading-6 text-slate-700">I certify that the information provided in this application is true and complete. I understand that false information may result in disqualification.</span>
                    </label>
                </section>

                <div class="flex flex-col-reverse justify-end gap-3 sm:flex-row">
                    <a href="<?= $isEdit ? portal_url('dashboard.php') : portal_url('jobs.php') ?>" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-6 py-3.5 font-bold text-slate-700 transition hover:bg-slate-100">Cancel</a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-government-navy px-8 py-3.5 font-bold text-white shadow-lg transition hover:bg-government-blue focus:ring-4 focus:ring-blue-200"><?= $isEdit ? 'Save Changes' : 'Submit Application' ?></button>
                </div>
            </form>
        </main>
    </div>
</div>

<script>
    let educationIndex = <?= count($educationSource) ?>;
    let experienceIndex = <?= count($experienceSource) ?>;
    let trainingIndex = <?= count($trainingSource) ?>;
    let eligibilityIndex = <?= count($eligibilitySource) ?>;

    const inputClass = 'w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100';

    function removeEntry(button) { button.closest('.dynamic-entry').remove(); }

    function toggleEducationSpecify(selectEl) {
        const entry = selectEl.closest('.dynamic-entry');
        const specifyInput = entry.querySelector('[data-role="education-specify"]');
        if (selectEl.value === "Other's") { specifyInput.classList.remove('hidden'); }
        else { specifyInput.classList.add('hidden'); specifyInput.value = ''; }
    }

    function addEducation() {
        const wrapper = document.getElementById('educationWrapper');
        wrapper.insertAdjacentHTML('beforeend', `
            <div class="dynamic-entry rounded-xl border border-slate-200 bg-slate-50 p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h4 class="font-black text-government-dark">Education Entry</h4>
                    <button type="button" onclick="removeEntry(this)" class="text-sm font-bold text-red-600 hover:text-red-800">Remove</button>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <select name="education[${educationIndex}][level]" class="${inputClass}" onchange="toggleEducationSpecify(this)">
                        <option value="" disabled selected hidden>Education level</option>
                        <option value="Bachelor's Degree">Bachelor's Degree</option>
                        <option value="Master's Degree">Master's Degree</option>
                        <option value="Doctorate Degree">Doctorate Degree</option>
                        <option value="Other's">Other's</option>
                    </select>
                    <input type="text" name="education[${educationIndex}][level_specify]" data-role="education-specify" placeholder="Please specify" class="${inputClass} hidden">
                    <input type="text" name="education[${educationIndex}][school]" placeholder="School" class="${inputClass}">
                    <input type="text" name="education[${educationIndex}][degree]" placeholder="Degree or course" class="${inputClass}">
                    <input type="text" name="education[${educationIndex}][year_graduated]" placeholder="Year graduated" class="${inputClass}">
                </div>
            </div>
        `);
        educationIndex++;
    }

    function addExperience() {
        const wrapper = document.getElementById('experienceWrapper');
        wrapper.insertAdjacentHTML('beforeend', `
            <div class="dynamic-entry rounded-xl border border-slate-200 bg-slate-50 p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h4 class="font-black text-government-dark">Experience Entry</h4>
                    <button type="button" onclick="removeEntry(this)" class="text-sm font-bold text-red-600 hover:text-red-800">Remove</button>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <input type="text" name="experience[${experienceIndex}][title]" placeholder="Position title" class="${inputClass}">
                    <input type="text" name="experience[${experienceIndex}][company]" placeholder="Company or agency" class="${inputClass}">
                    <input type="text" name="experience[${experienceIndex}][first_day]" placeholder="First Day of Service (Month and Year)" class="${inputClass}">
                    <input type="text" name="experience[${experienceIndex}][last_day]" placeholder="Last Day of Service (Month and Year)" class="${inputClass}">
                    <textarea name="experience[${experienceIndex}][details]" placeholder="Responsibilities or details" rows="3" class="${inputClass}"></textarea>
                </div>
            </div>
        `);
        experienceIndex++;
    }

    function addTraining() {
        const wrapper = document.getElementById('trainingWrapper');
        wrapper.insertAdjacentHTML('beforeend', `
            <div class="dynamic-entry rounded-xl border border-slate-200 bg-slate-50 p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h4 class="font-black text-government-dark">Training Entry</h4>
                    <button type="button" onclick="removeEntry(this)" class="text-sm font-bold text-red-600 hover:text-red-800">Remove</button>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <input type="text" name="training[${trainingIndex}][title]" placeholder="Training or seminar title" class="${inputClass}">
                    <input type="number" min="0" step="1" name="training[${trainingIndex}][hours]" placeholder="Number of hours" class="${inputClass}">
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-bold text-government-dark">Start of Training</label>
                        <input type="month" name="training[${trainingIndex}][training_date]" max="<?= date('Y-m') ?>" class="${inputClass}">
                        <label class="mb-2 mt-3 block text-sm font-bold text-government-dark">End of Training</label>
                        <input type="month" name="training[${trainingIndex}][training_end_date]" max="<?= date('Y-m') ?>" class="${inputClass}">
                        <p class="mt-2 text-xs text-slate-500">Select the month and year when the training or seminar started and ended.</p>
                    </div>
                </div>
            </div>
        `);
        trainingIndex++;
    }

    function toggleEligibilitySpecify(selectEl) {
        const entry = selectEl.closest('.dynamic-entry');
        const specifyInput = entry.querySelector('[data-role="eligibility-specify"]');
        if (selectEl.value === 'RA1080' || selectEl.value === "Other's") { specifyInput.classList.remove('hidden'); }
        else { specifyInput.classList.add('hidden'); specifyInput.value = ''; }
    }

    function toggleNeverExpires(checkboxEl) {
        const entry = checkboxEl.closest('.dynamic-entry');
        const wrapper = entry.querySelector('[data-role="valid-until-wrapper"]');
        const input = wrapper.querySelector('input');
        if (checkboxEl.checked) { wrapper.classList.add('hidden'); input.value = ''; }
        else { wrapper.classList.remove('hidden'); }
    }

    function addEligibility() {
        const wrapper = document.getElementById('eligibilityWrapper');
        wrapper.insertAdjacentHTML('beforeend', `
            <div class="dynamic-entry rounded-xl border border-slate-200 bg-slate-50 p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h4 class="font-black text-government-dark">Eligibility Entry</h4>
                    <button type="button" onclick="removeEntry(this)" class="text-sm font-bold text-red-600 hover:text-red-800">Remove</button>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <select name="eligibility[${eligibilityIndex}][license_name]" class="${inputClass}" onchange="toggleEligibilitySpecify(this)">
                        <option value="" disabled selected hidden>Eligibility or license name</option>
                        <option value="CS Sub-Professional">CS Sub-Professional</option>
                        <option value="CSC Professional">CSC Professional</option>
                        <option value="RA1080">RA1080</option>
                        <option value="Other's">Other's</option>
                    </select>
                    <input type="text" name="eligibility[${eligibilityIndex}][license_specify]" data-role="eligibility-specify" placeholder="Please specify" class="${inputClass} hidden">
                    <input type="text" name="eligibility[${eligibilityIndex}][rating]" placeholder="Rating" class="${inputClass}">
                    <div>
                        <label class="mb-2 block text-sm font-bold text-government-dark">Date Issued</label>
                        <input type="date" name="eligibility[${eligibilityIndex}][date_issued]" class="${inputClass}">
                    </div>
                    <div data-role="valid-until-wrapper">
                        <label class="mb-2 block text-sm font-bold text-government-dark">Valid Until</label>
                        <input type="date" name="eligibility[${eligibilityIndex}][valid_until]" class="${inputClass}">
                    </div>
                    <label class="mt-1 ml-auto flex w-fit items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 md:col-span-2">
                        <input type="checkbox" name="eligibility[${eligibilityIndex}][never_expires]" value="1" class="h-4 w-4 rounded border-slate-300 text-government-navy" onchange="toggleNeverExpires(this)">
                        <span class="text-xs font-semibold text-slate-700">Never expires</span>
                    </label>
                </div>
            </div>
        `);
        eligibilityIndex++;
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (educationIndex === 0) addEducation();
        if (experienceIndex === 0) addExperience();
        if (trainingIndex === 0) addTraining();
        if (eligibilityIndex === 0) addEligibility();
    });
</script>

<?php require __DIR__.'/_layout_footer.php'; ?>
