<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

portal_require_login();
$user = portal_user();

$stmt = portal_pdo()->prepare(
    'SELECT a.id AS application_id, a.status, p.*
     FROM applications a
     LEFT JOIN applicant_profiles p ON p.application_id = a.id
     WHERE a.applicant_id = ?
     ORDER BY a.created_at DESC
     LIMIT 1'
);
$stmt->execute([$user->id]);
$latest = $stmt->fetch();

$personalErrors = new Errors();
$passwordErrors = new Errors();
$activeTab = 'personal';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'update_personal_info') {
    csrf_verify();

    if (!$latest || $latest->status !== 'pending') {
        flash_set('error', 'Personal information can no longer be edited since your application has already been evaluated.');
        header('Location: '.portal_url('profile.php'));
        exit;
    }

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

    if (!v_present($fullName)) { $personalErrors->add('full_name', 'The full name field is required.'); }
    elseif (!v_max($fullName, 255)) { $personalErrors->add('full_name', 'The full name field must not be greater than 255 characters.'); }

    if (!v_present($email)) { $personalErrors->add('email', 'The email field is required.'); }
    elseif (!v_email($email)) { $personalErrors->add('email', 'The email field must be a valid email address.'); }

    if ($birthDate !== '' && (!v_date($birthDate) || !v_date_before_or_equal_today($birthDate))) {
        $personalErrors->add('birth_date', 'The birth date field must be a date before or equal to today.');
    }

    if (!v_present($religion)) { $personalErrors->add('religion', 'The religion field is required.'); }

    if (!$personalErrors->any()) {
        portal_pdo()->prepare(
            'UPDATE applicant_profiles SET full_name=?, email=?, phone=?, address=?, birth_date=?, sex=?, civil_status=?, religion=?, disability=?, ethnic_group=?, updated_at=?
             WHERE application_id = ?'
        )->execute([
            $fullName, $email, $phone ?: null, $address ?: null, $birthDate ?: null, $sex ?: null,
            $civilStatus ?: null, $religion, $disability ?: null, $ethnicGroup ?: null,
            date('Y-m-d H:i:s'), $latest->application_id,
        ]);

        flash_set('success', 'Personal information updated successfully.');
        header('Location: '.portal_url('profile.php'));
        exit;
    }

    flash_set('_personal_errors', $personalErrors->all());
    set_old_input([
        'full_name' => $fullName, 'email' => $email, 'phone_number' => $phone, 'address' => $address,
        'birth_date' => $birthDate, 'sex' => $sex, 'civil_status' => $civilStatus, 'religion' => $religion,
        'disability' => $disability, 'ethnic_group' => $ethnicGroup,
    ]);
    header('Location: '.portal_url('profile.php').'#personal');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'update_password') {
    csrf_verify();

    $current = (string) ($_POST['current_password'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');

    $stmt = portal_pdo()->prepare('SELECT password FROM applicants WHERE id = ?');
    $stmt->execute([$user->id]);
    $row = $stmt->fetch();

    if (!v_present($current) || !password_verify($current, $row->password)) {
        $passwordErrors->add('current_password', 'The current password is incorrect.');
    }
    if (!v_present($password)) { $passwordErrors->add('password', 'The password field is required.'); }
    elseif (!v_min($password, 8)) { $passwordErrors->add('password', 'The password field must be at least 8 characters.'); }
    elseif ($password !== $confirmation) { $passwordErrors->add('password', 'The password field confirmation does not match.'); }

    if (!$passwordErrors->any()) {
        portal_pdo()->prepare('UPDATE applicants SET password=?, updated_at=? WHERE id=?')->execute([
            password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]), date('Y-m-d H:i:s'), $user->id,
        ]);

        flash_set('success', 'Password changed successfully.');
        header('Location: '.portal_url('profile.php'));
        exit;
    }

    flash_set('_password_errors', $passwordErrors->all());
    header('Location: '.portal_url('profile.php').'#password');
    exit;
}

$personalErrors = new Errors();
foreach ((flash_get('_personal_errors', [])) as $field => $message) { $personalErrors->add($field, $message); }
$passwordErrors = new Errors();
foreach ((flash_get('_password_errors', [])) as $field => $message) { $passwordErrors->add($field, $message); }
if ($passwordErrors->any()) { $activeTab = 'password'; }

$civilStatuses = ['Single', 'Married', 'Widowed', 'Legally Separated', 'Divorced', 'Annulled', 'Other'];

$pageTitle = 'My Profile | DepEd Recruitment Portal';
require __DIR__.'/_layout_head.php';
?>

<?php $activePage = 'profile'; require __DIR__.'/_layout_topbar.php'; ?>

<div class="lg:flex lg:flex-1">
    <?php require __DIR__.'/_layout_sidebar.php'; ?>

    <div class="min-w-0 flex-1">
        <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">

            <?php if (flash_get('success')): ?>
                <div class="mb-8 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800"><?= portal_e(flash_get('success')) ?></div>
            <?php endif; ?>
            <?php if (flash_get('error')): ?>
                <div class="mb-8 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800"><?= portal_e(flash_get('error')) ?></div>
            <?php endif; ?>

            <div class="mb-8 border-b border-slate-200 pb-6">
                <p class="text-sm font-bold uppercase tracking-widest text-government-blue">My Account</p>
                <h2 class="mt-2 text-3xl font-black text-government-dark">My Profile</h2>
                <p class="mt-2 text-slate-600">Manage your personal information and password.</p>
            </div>

            <div class="flex flex-col gap-6 md:flex-row md:items-start">
                <nav class="flex shrink-0 gap-2 overflow-x-auto md:w-56 md:flex-col md:gap-1 md:overflow-visible">
                    <button type="button" data-profile-tab="personal" class="profile-tab-link whitespace-nowrap rounded-lg px-4 py-2.5 text-left text-sm font-bold transition">Personal Information</button>
                    <button type="button" data-profile-tab="password" class="profile-tab-link whitespace-nowrap rounded-lg px-4 py-2.5 text-left text-sm font-bold transition">Change Password</button>
                </nav>

                <div class="flex-1">
                    <div data-profile-panel="personal" class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                        <h3 class="mb-1 text-lg font-black text-government-dark">Personal Information</h3>
                        <p class="mb-6 text-sm text-slate-500">This is the personal information you provided on your most recent application.</p>

                        <?php if ($latest && $latest->status === 'pending'): ?>
                            <?php $selectedSex = portal_old('sex', $latest->sex); $selectedCivilStatus = portal_old('civil_status', $latest->civil_status); ?>
                            <form method="POST" action="<?= portal_url('profile.php') ?>" class="space-y-5">
                                <?= portal_csrf_field() ?>
                                <input type="hidden" name="_action" value="update_personal_info">

                                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <label class="mb-1 block text-sm font-bold text-slate-700">Full Name</label>
                                        <input type="text" name="full_name" value="<?= portal_e(portal_old('full_name', $latest->full_name)) ?>" placeholder="Last Name|First Name|Middle Name|Name Extension" required class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20">
                                        <?php if ($personalErrors->has('full_name')): ?><p class="mt-1 text-sm text-red-600"><?= portal_e($personalErrors->first('full_name')) ?></p><?php endif; ?>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-bold text-slate-700">Email Address</label>
                                        <input type="email" name="email" value="<?= portal_e(portal_old('email', $latest->email)) ?>" required class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20">
                                        <?php if ($personalErrors->has('email')): ?><p class="mt-1 text-sm text-red-600"><?= portal_e($personalErrors->first('email')) ?></p><?php endif; ?>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-bold text-slate-700">Phone Number</label>
                                        <input type="text" name="phone_number" value="<?= portal_e(portal_old('phone_number', $latest->phone)) ?>" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="mb-1 block text-sm font-bold text-slate-700">Complete Address</label>
                                        <input type="text" name="address" value="<?= portal_e(portal_old('address', $latest->address)) ?>" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-bold text-slate-700">Birth Date</label>
                                        <input type="date" name="birth_date" value="<?= portal_e(portal_old('birth_date', $latest->birth_date)) ?>" max="<?= date('Y-m-d') ?>" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20">
                                        <?php if ($personalErrors->has('birth_date')): ?><p class="mt-1 text-sm text-red-600"><?= portal_e($personalErrors->first('birth_date')) ?></p><?php endif; ?>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-bold text-slate-700">Sex</label>
                                        <select name="sex" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20">
                                            <option value="">Select sex</option>
                                            <option value="Male" <?= $selectedSex === 'Male' ? 'selected' : '' ?>>Male</option>
                                            <option value="Female" <?= $selectedSex === 'Female' ? 'selected' : '' ?>>Female</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-bold text-slate-700">Civil Status</label>
                                        <select name="civil_status" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20">
                                            <option value="">Select civil status</option>
                                            <?php foreach ($civilStatuses as $cs): ?>
                                                <option value="<?= portal_e($cs) ?>" <?= $selectedCivilStatus === $cs ? 'selected' : '' ?>><?= portal_e($cs) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-bold text-slate-700">Religion</label>
                                        <input type="text" name="religion" value="<?= portal_e(portal_old('religion', $latest->religion)) ?>" required class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20">
                                        <?php if ($personalErrors->has('religion')): ?><p class="mt-1 text-sm text-red-600"><?= portal_e($personalErrors->first('religion')) ?></p><?php endif; ?>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-bold text-slate-700">Disability (if any)</label>
                                        <input type="text" name="disability" value="<?= portal_e(portal_old('disability', $latest->disability)) ?>" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-bold text-slate-700">Ethnic Group</label>
                                        <input type="text" name="ethnic_group" value="<?= portal_e(portal_old('ethnic_group', $latest->ethnic_group)) ?>" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20">
                                    </div>
                                </div>

                                <button type="submit" class="rounded-xl bg-government-navy px-5 py-3 font-bold text-white transition hover:bg-government-blue">Save Changes</button>
                            </form>
                        <?php elseif ($latest): ?>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="rounded-lg border border-slate-200 px-4 py-2.5"><p class="text-sm font-bold text-slate-700">Full Name</p><span class="text-slate-600"><?= portal_e($latest->full_name ?: '—') ?></span></div>
                                <div class="rounded-lg border border-slate-200 px-4 py-2.5"><p class="text-sm font-bold text-slate-700">Email Address</p><span class="text-slate-600"><?= portal_e($latest->email ?: '—') ?></span></div>
                                <div class="rounded-lg border border-slate-200 px-4 py-2.5"><p class="text-sm font-bold text-slate-700">Phone Number</p><span class="text-slate-600"><?= portal_e($latest->phone ?: '—') ?></span></div>
                                <div class="rounded-lg border border-slate-200 px-4 py-2.5"><p class="text-sm font-bold text-slate-700">Address</p><span class="text-slate-600"><?= portal_e($latest->address ?: '—') ?></span></div>
                                <div class="rounded-lg border border-slate-200 px-4 py-2.5"><p class="text-sm font-bold text-slate-700">Birth Date</p><span class="text-slate-600"><?= $latest->birth_date ? portal_e(date('M d, Y', strtotime($latest->birth_date))) : '—' ?></span></div>
                                <div class="rounded-lg border border-slate-200 px-4 py-2.5"><p class="text-sm font-bold text-slate-700">Sex</p><span class="text-slate-600"><?= portal_e($latest->sex ?: '—') ?></span></div>
                                <div class="rounded-lg border border-slate-200 px-4 py-2.5"><p class="text-sm font-bold text-slate-700">Civil Status</p><span class="text-slate-600"><?= portal_e($latest->civil_status ?: '—') ?></span></div>
                                <div class="rounded-lg border border-slate-200 px-4 py-2.5"><p class="text-sm font-bold text-slate-700">Religion</p><span class="text-slate-600"><?= portal_e($latest->religion ?: '—') ?></span></div>
                                <div class="rounded-lg border border-slate-200 px-4 py-2.5"><p class="text-sm font-bold text-slate-700">Disability (if any)</p><span class="text-slate-600"><?= portal_e($latest->disability ?: '—') ?></span></div>
                                <div class="rounded-lg border border-slate-200 px-4 py-2.5"><p class="text-sm font-bold text-slate-700">Ethnic Group</p><span class="text-slate-600"><?= portal_e($latest->ethnic_group ?: '—') ?></span></div>
                            </div>
                            <p class="mt-6 text-sm text-slate-500">This information can no longer be edited since your application has already been evaluated.</p>
                        <?php else: ?>
                            <p class="text-slate-500">You haven't submitted an application yet. Personal information will appear here once you apply for a position.</p>
                        <?php endif; ?>
                    </div>

                    <div data-profile-panel="password" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                        <h3 class="mb-6 text-lg font-black text-government-dark">Change Password</h3>
                        <form method="POST" action="<?= portal_url('profile.php') ?>" class="space-y-5">
                            <?= portal_csrf_field() ?>
                            <input type="hidden" name="_action" value="update_password">

                            <div>
                                <label class="mb-1 block text-sm font-bold text-slate-700">Current Password</label>
                                <input type="password" name="current_password" required class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20">
                                <?php if ($passwordErrors->has('current_password')): ?><p class="mt-1 text-sm text-red-600"><?= portal_e($passwordErrors->first('current_password')) ?></p><?php endif; ?>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-bold text-slate-700">New Password</label>
                                <input type="password" name="password" required class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20">
                                <?php if ($passwordErrors->has('password')): ?><p class="mt-1 text-sm text-red-600"><?= portal_e($passwordErrors->first('password')) ?></p><?php endif; ?>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-bold text-slate-700">Confirm New Password</label>
                                <input type="password" name="password_confirmation" required class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20">
                            </div>

                            <button type="submit" class="rounded-xl bg-government-navy px-5 py-3 font-bold text-white transition hover:bg-government-blue">Update Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
    .profile-tab-link { color: #475569; }
    .profile-tab-link:hover { background: #f1f5f9; }
    .profile-tab-link.is-active { background: #123B6D; color: #ffffff; }
</style>

<script>
    (function () {
        const tabs = document.querySelectorAll('[data-profile-tab]');
        const panels = document.querySelectorAll('[data-profile-panel]');
        function activate(tabName) {
            tabs.forEach((tab) => tab.classList.toggle('is-active', tab.dataset.profileTab === tabName));
            panels.forEach((panel) => { panel.style.display = panel.dataset.profilePanel === tabName ? 'block' : 'none'; });
        }
        tabs.forEach((tab) => tab.addEventListener('click', () => activate(tab.dataset.profileTab)));
        activate(<?= json_encode($activeTab) ?>);
    })();
</script>

<?php require __DIR__.'/_layout_footer.php'; ?>
