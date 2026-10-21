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
        <main class="px-4 py-8 sm:px-6 lg:px-10">

            <div class="mb-6 border-b border-slate-200 pb-6">
                <h2 class="mt-2 text-3xl font-black text-admin-indigoDark">Publication of Vacancy</h2>
                <p class="mt-2 text-slate-600">CSC Publication of Vacancy documents uploaded for each posted position.</p>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex justify-end border-b border-slate-200 px-4 py-3 sm:px-6">
                    <div class="relative w-full max-w-xs">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <input type="search" id="publication-search" placeholder="Search" class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm outline-none focus:border-admin-indigo focus:ring-2 focus:ring-admin-indigo/20">
                    </div>
                </div>

                <div class="overflow-x-auto">
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
                        <tbody id="publication-tbody" class="divide-y divide-slate-100">
                            <?php if (empty($positions)): ?>
                                <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">No positions have been posted yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($positions as $p):
                                $cscPaths = json_decode($p->csc_publication_paths ?? '[]', true) ?: [];
                                $isOpen = ((int) $p->is_open === 1) && !admin_job_deadline_passed($p->until, $p->until_time);
                            ?>
                                <tr class="publication-row hover:bg-slate-50" data-search="<?= admin_e(mb_strtolower($p->title)) ?>">
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
                                        <a href="<?= admin_url('job-position-edit.php?id='.$p->id.'&from=publication-of-vacancy') ?>" class="font-bold text-admin-indigo hover:underline">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    (function () {
        var search = document.getElementById('publication-search');
        if (!search) return;
        search.addEventListener('input', function () {
            var term = search.value.trim().toLowerCase();
            document.querySelectorAll('.publication-row').forEach(function (row) {
                row.classList.toggle('hidden', term !== '' && row.dataset.search.indexOf(term) === -1);
            });
        });
    })();
</script>

<?php require __DIR__.'/_layout_footer.php'; ?>
