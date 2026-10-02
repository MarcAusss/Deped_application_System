<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

admin_require_login();
$pdo = portal_pdo();

$positions = $pdo->query(
    "SELECT * FROM job_positions WHERE posted_at IS NOT NULL ORDER BY created_at DESC"
)->fetchAll();

$pageTitle = 'Publication of Vacancy | Administration';
require __DIR__.'/_layout_head.php';
?>

<?php $activePage = 'publication-of-vacancy'; require __DIR__.'/_layout_topbar.php'; ?>

<div class="lg:flex lg:flex-1">
    <?php require __DIR__.'/_layout_sidebar.php'; ?>

    <div class="min-w-0 flex-1">
        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">

            <div class="mb-6 border-b border-slate-200 pb-6">
                <p class="text-sm font-bold uppercase tracking-widest text-admin-indigo">Recruitment</p>
                <h2 class="mt-2 text-3xl font-black text-admin-indigoDark">Publication of Vacancy</h2>
                <p class="mt-2 text-slate-600">CSC Publication of Vacancy documents uploaded for each posted position.</p>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-bold text-slate-600">Job Position</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-600">Open</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-600">Posted</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-600">Until</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-600">References</th>
                            <th class="px-4 py-3 text-center font-bold text-slate-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($positions)): ?>
                            <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">No positions have been posted yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($positions as $p):
                            $cscPaths = json_decode($p->csc_publication_paths ?? '[]', true) ?: [];
                            $isOpen = ((int) $p->is_open === 1) && !admin_job_deadline_passed($p->until, $p->until_time);
                        ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-bold text-slate-800"><?= admin_e($p->title) ?></td>
                                <td class="px-4 py-3"><?= $isOpen ? '<span class="text-emerald-600">&#10003;</span>' : '<span class="text-slate-300">&#10007;</span>' ?></td>
                                <td class="px-4 py-3 text-slate-600"><?= $p->posted_at ? admin_e(date('M d, Y', strtotime($p->posted_at))) : '—' ?></td>
                                <td class="px-4 py-3 text-slate-600"><?= $p->until ? admin_e(date('M d, Y', strtotime($p->until))) : '—' ?></td>
                                <td class="px-4 py-3">
                                    <?php if (empty($cscPaths)): ?>
                                        <span class="text-slate-400">—</span>
                                    <?php else: ?>
                                        <?php foreach ($cscPaths as $path): ?>
                                            <a href="<?= admin_url('document.php?path='.urlencode($path)) ?>" target="_blank" class="pill mb-1 mr-1 inline-block bg-admin-indigoLight text-admin-indigo"><?= admin_e(basename($path)) ?></a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
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
