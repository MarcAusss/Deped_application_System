<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

admin_require_login();
$pdo = portal_pdo();

$sortBy = in_array($_GET['sort'] ?? '', ['title', 'slots'], true) ? $_GET['sort'] : 'title';
$sortDir = ($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

$positions = $pdo->query("SELECT * FROM job_positions ORDER BY {$sortBy} {$sortDir}")->fetchAll();

function admin_sort_link(string $column, string $label, string $currentSort, string $currentDir): string
{
    $nextDir = ($currentSort === $column && $currentDir === 'ASC') ? 'desc' : 'asc';
    $isActive = $currentSort === $column;
    $rotate = $isActive && $currentDir === 'DESC' ? 'rotate-180' : '';

    return '<a href="'.admin_url('job-positions.php?sort='.$column.'&dir='.$nextDir).'" class="inline-flex items-center gap-1 hover:text-admin-indigo">'
        .htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
        .'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5 transition-transform '.$rotate.' '.($isActive ? '' : 'opacity-50').'">'
        .'<path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg></a>';
}

$pageTitle = 'Job Positions | Administration';
require __DIR__.'/_layout_head.php';
?>

<?php $activePage = 'job-positions'; require __DIR__.'/_layout_topbar.php'; ?>

<div class="lg:flex lg:flex-1">
    <?php require __DIR__.'/_layout_sidebar.php'; ?>

    <div class="min-w-0 flex-1">
        <main class="px-4 py-8 sm:px-6 lg:px-10">

            <?php if (flash_get('success')): ?>
                <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800"><?= admin_e(flash_get('success')) ?></div>
            <?php endif; ?>
            <?php if (flash_get('error')): ?>
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800"><?= admin_e(flash_get('error')) ?></div>
            <?php endif; ?>

            <div class="mb-6 flex flex-wrap items-end justify-between gap-3 border-b border-slate-200 pb-6">
                <div>
                    <h2 class="mt-2 text-3xl font-black text-admin-indigoDark">Job Positions</h2>
                    <p class="mt-2 text-slate-600">Create and manage the positions available for recruitment.</p>
                </div>
                <a href="<?= admin_url('job-position-create.php') ?>" class="rounded-lg bg-admin-indigo px-4 py-2 text-sm font-bold text-white hover:bg-admin-indigoDark">+ New Job Position</a>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex justify-end border-b border-slate-200 px-4 py-3 sm:px-6">
                    <div class="relative w-full max-w-xs">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <input type="search" id="job-positions-search" placeholder="Search" class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm outline-none focus:border-admin-indigo focus:ring-2 focus:ring-admin-indigo/20">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold text-slate-600"><?= admin_sort_link('title', 'Title', $sortBy, $sortDir) ?></th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Acronym</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600"><?= admin_sort_link('slots', 'No. of Vacancies', $sortBy, $sortDir) ?></th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Open</th>
                                <th class="px-4 py-3 text-center font-bold text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="job-positions-tbody" class="divide-y divide-slate-100">
                            <?php if (empty($positions)): ?>
                                <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">No job positions yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($positions as $p): $isOpen = ((int) $p->is_open === 1) && !admin_job_deadline_passed($p->until, $p->until_time); ?>
                                <tr class="job-position-row hover:bg-slate-50" data-search="<?= admin_e(mb_strtolower($p->title.' '.$p->abbreviation)) ?>">
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
            </div>
        </main>
    </div>
</div>

<script>
    (function () {
        var search = document.getElementById('job-positions-search');
        if (!search) return;
        search.addEventListener('input', function () {
            var term = search.value.trim().toLowerCase();
            document.querySelectorAll('.job-position-row').forEach(function (row) {
                row.classList.toggle('hidden', term !== '' && row.dataset.search.indexOf(term) === -1);
            });
        });
    })();
</script>

<?php require __DIR__.'/_layout_footer.php'; ?>
