<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

admin_require_login();
$pdo = portal_pdo();

$positions = $pdo->query('SELECT * FROM job_positions ORDER BY title ASC')->fetchAll();

$pageTitle = 'Job Positions | Administration';
require __DIR__.'/_layout_head.php';
?>

<?php $activePage = 'job-positions'; require __DIR__.'/_layout_topbar.php'; ?>

<div class="lg:flex lg:flex-1">
    <?php require __DIR__.'/_layout_sidebar.php'; ?>

    <div class="min-w-0 flex-1">
        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">

            <?php if (flash_get('success')): ?>
                <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800"><?= admin_e(flash_get('success')) ?></div>
            <?php endif; ?>
            <?php if (flash_get('error')): ?>
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800"><?= admin_e(flash_get('error')) ?></div>
            <?php endif; ?>

            <div class="mb-6 flex flex-wrap items-end justify-between gap-3 border-b border-slate-200 pb-6">
                <div>
                    <p class="text-sm font-bold uppercase tracking-widest text-admin-indigo">Recruitment</p>
                    <h2 class="mt-2 text-3xl font-black text-admin-indigoDark">Job Positions</h2>
                    <p class="mt-2 text-slate-600">Create and manage the positions available for recruitment.</p>
                </div>
                <a href="<?= admin_url('job-position-create.php') ?>" class="rounded-lg bg-admin-indigo px-4 py-2 text-sm font-bold text-white hover:bg-admin-indigoDark">+ New Job Position</a>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-bold text-slate-600">Title</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-600">Acronym</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-600">No. of Vacancies</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-600">Open</th>
                            <th class="px-4 py-3 text-center font-bold text-slate-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($positions)): ?>
                            <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">No job positions yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($positions as $p): $isOpen = ((int) $p->is_open === 1) && !admin_job_deadline_passed($p->until, $p->until_time); ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-bold text-slate-800"><?= admin_e($p->title) ?></td>
                                <td class="px-4 py-3 text-slate-600"><?= admin_e($p->abbreviation ?: '—') ?></td>
                                <td class="px-4 py-3 text-slate-600"><?= admin_e((string) $p->slots) ?></td>
                                <td class="px-4 py-3"><?= $isOpen ? '<span class="text-emerald-600">&#10003;</span>' : '<span class="text-slate-300">&#10007;</span>' ?></td>
                                <td class="px-4 py-3 text-center">
                                    <a href="<?= admin_url('job-position-edit.php?id='.$p->id) ?>" class="font-bold text-admin-indigo hover:underline">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<?php require __DIR__.'/_layout_footer.php'; ?>
