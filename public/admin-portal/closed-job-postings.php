<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

admin_require_login();
$pdo = portal_pdo();

$postings = $pdo->query(
    "SELECT * FROM job_positions
     WHERE posted_at IS NOT NULL
       AND (is_open = 0 OR (`until` IS NOT NULL AND TIMESTAMP(`until`, COALESCE(until_time, '23:59:59')) < NOW()))
     ORDER BY updated_at DESC"
)->fetchAll();

$pageTitle = 'Closed Postings | Administration';
require __DIR__.'/_layout_head.php';
?>

<?php $activePage = 'closed-job-postings'; require __DIR__.'/_layout_topbar.php'; ?>

<div class="lg:flex lg:flex-1">
    <?php require __DIR__.'/_layout_sidebar.php'; ?>

    <div class="min-w-0 flex-1">
        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">

            <?php if (flash_get('success')): ?>
                <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800"><?= admin_e(flash_get('success')) ?></div>
            <?php endif; ?>

            <div class="mb-6 border-b border-slate-200 pb-6">
                <p class="text-sm font-bold uppercase tracking-widest text-admin-indigo">Recruitment</p>
                <h2 class="mt-2 text-3xl font-black text-admin-indigoDark">Closed Postings</h2>
                <p class="mt-2 text-slate-600">Positions that were posted but are no longer open.</p>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-bold text-slate-600">JP Number</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-600">Title</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-600">Until</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-600">Reason</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-600">References</th>
                            <th class="px-4 py-3 text-center font-bold text-slate-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($postings)): ?>
                            <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">No closed postings.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($postings as $p):
                            $attachments = json_decode($p->attachment_paths ?? '[]', true) ?: [];
                            $deadlinePassed = admin_job_deadline_passed($p->until, $p->until_time);
                        ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-mono text-xs"><?= admin_e($p->jp_number ?: '—') ?></td>
                                <td class="px-4 py-3 font-bold text-slate-800"><?= admin_e($p->title) ?></td>
                                <td class="px-4 py-3 text-slate-600"><?= $p->until ? admin_e(date('M d, Y', strtotime($p->until))) : '—' ?></td>
                                <td class="px-4 py-3"><span class="pill <?= $deadlinePassed ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600' ?>"><?= $deadlinePassed ? 'Deadline passed' : 'Closed manually' ?></span></td>
                                <td class="px-4 py-3">
                                    <?php if (empty($attachments)): ?>
                                        <span class="text-slate-400">—</span>
                                    <?php else: ?>
                                        <?php foreach ($attachments as $path): ?>
                                            <a href="<?= admin_url('document.php?path='.urlencode($path)) ?>" target="_blank" class="pill mb-1 mr-1 inline-block bg-admin-indigoLight text-admin-indigo"><?= admin_e(basename($path)) ?></a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="<?= admin_url('job-position-publish.php?id='.$p->id.'&return=closed-job-postings') ?>" class="font-bold text-admin-indigo hover:underline">Repost</a>
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
