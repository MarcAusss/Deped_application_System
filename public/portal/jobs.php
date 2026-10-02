<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';

$stmt = portal_pdo()->query(
    "SELECT * FROM job_positions
     WHERE is_open = 1
       AND (until IS NULL OR TIMESTAMP(until, COALESCE(until_time, '23:59:59')) >= NOW())
     ORDER BY created_at DESC"
);
$jobs = $stmt->fetchAll();

function job_paths(?string $json): array
{
    if (!$json) {
        return [];
    }
    $decoded = json_decode($json, true);

    return is_array($decoded) ? $decoded : [];
}

$pageTitle = 'Available Job Positions | DepEd Recruitment Portal';
require __DIR__.'/_layout_head.php';
?>

<?php $activePage = 'jobs'; require __DIR__.'/_layout_topbar.php'; ?>

<section class="bg-gradient-to-r from-government-dark to-government-blue text-white">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-8 px-4 py-10 sm:px-6 lg:px-8">
        <div>
            <p class="text-lg font-black uppercase tracking-widest text-white">Welcome!</p>
            <h2 class="mt-4 max-w-3xl font-black leading-tight">
                <span class="text-5xl sm:text-5xl">SDO ALBAY CARES</span><br>
                <span class="text-2xl sm:text-3xl">(Career Application &<br>Recruitment for Education Services)</span>
            </h2>
            <p class="mt-5 max-w-2xl text-lg leading-8 text-blue-100">
                Welcome! Every great career starts with a single step — explore our open positions and apply today.
            </p>
        </div>
        <img src="/images/depedalbay.png" alt="DepEd Division of Albay" class="hidden h-64 w-64 shrink-0 -translate-x-12 object-contain lg:block">
    </div>
</section>

<div class="lg:flex lg:flex-1">
    <?php require __DIR__.'/_layout_sidebar.php'; ?>

    <div class="min-w-0 flex-1">
        <main class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">

            <?php if (flash_get('success')): ?>
                <div class="mb-8 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800"><?= portal_e(flash_get('success')) ?></div>
            <?php endif; ?>
            <?php if (flash_get('error')): ?>
                <div class="mb-8 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800"><?= portal_e(flash_get('error')) ?></div>
            <?php endif; ?>

            <div class="mb-8 flex flex-col justify-between gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end">
                <div>
                    <p class="text-sm font-bold uppercase tracking-widest text-government-blue">Recruitment Opportunities</p>
                    <h2 class="mt-2 text-3xl font-black text-government-dark">Available Job Positions</h2>
                    <p class="mt-2 text-slate-600">Select a position to proceed to the online application form.</p>
                </div>
                <div class="rounded-xl border border-blue-100 bg-government-light px-5 py-3">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Open Positions</p>
                    <p class="text-2xl font-black text-government-navy"><?= count($jobs) ?></p>
                </div>
            </div>

            <?php if (empty($jobs)): ?>
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                    <h3 class="text-2xl font-black text-government-dark">No available job positions</h3>
                    <p class="mt-3 text-slate-500">Please check again for future employment opportunities.</p>
                </div>
            <?php else: ?>
                <?php foreach ($jobs as $job): $attachments = job_paths($job->attachment_paths); $cscPaths = job_paths($job->csc_publication_paths); ?>
                    <article class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                        <div class="grid lg:grid-cols-[1fr_280px]">
                            <div class="p-6 sm:p-8">
                                <div class="mb-5 flex flex-wrap gap-3">
                                    <span class="rounded-full bg-green-50 px-3 py-1.5 text-xs font-bold uppercase text-green-700 ring-1 ring-green-200">Open for Application</span>
                                    <span class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold text-government-blue ring-1 ring-blue-200">Government Position</span>
                                </div>

                                <h3 class="text-2xl font-black text-government-dark sm:text-3xl"><?= portal_e($job->title) ?></h3>
                                <div class="mt-4 h-1 w-20 rounded-full bg-government-gold"></div>
                                <p class="mt-5 whitespace-pre-line leading-7 text-slate-600"><?= portal_e($job->description ?: 'No description has been provided for this position.') ?></p>

                                <div class="mt-7 flex flex-wrap items-start justify-between gap-x-6 gap-y-3 border-t border-slate-100 pt-5 text-sm text-slate-600">
                                    <?php if ($attachments || $cscPaths): ?>
                                        <div class="flex flex-wrap items-center gap-4">
                                            <?php foreach ($attachments as $index => $path): ?>
                                                <a href="<?= portal_url('document.php?path='.urlencode($path)) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-sm font-bold text-government-blue hover:text-government-navy hover:underline">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9.75v6.75m0 0-3-3m3 3 3-3m-8.25 6a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                                                    </svg>
                                                    D.M Notice<?= count($attachments) > 1 ? ' '.($index + 1) : '' ?>
                                                </a>
                                            <?php endforeach; ?>
                                            <?php foreach ($cscPaths as $index => $path): ?>
                                                <a href="<?= portal_url('document.php?path='.urlencode($path)) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-sm font-bold text-government-blue hover:text-government-navy hover:underline">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9.75v6.75m0 0-3-3m3 3 3-3m-8.25 6a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                                                    </svg>
                                                    CSC Publication of Vacancy<?= count($cscPaths) > 1 ? ' '.($index + 1) : '' ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="flex flex-wrap gap-x-6 gap-y-1">
                                        <div>Posted: <span class="font-bold text-slate-800"><?= portal_e(date('F d, Y', strtotime($job->posted_at ?? $job->created_at))) ?></span></div>
                                        <?php if ($job->until): ?>
                                            <div>Until: <span class="font-bold text-slate-800"><?= portal_e(date('F d, Y', strtotime($job->until))) ?><?= $job->until_time ? ' '.portal_e(date('g:i A', strtotime($job->until_time))) : '' ?></span></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="mt-3 flex flex-wrap items-start justify-between gap-x-6 gap-y-1">
                                    <?php if ($attachments || $cscPaths): ?>
                                        <p class="text-xs text-slate-500">Important: Review the D.M. Notice and CSC Publication of Vacancy for full qualifications, requirements, and deadlines.</p>
                                    <?php endif; ?>
                                    <p class="text-sm whitespace-nowrap text-slate-600">No. of Vacancies: <span class="font-bold text-slate-800"><?= (int) $job->slots ?></span></p>
                                </div>
                            </div>

                            <div class="flex items-center border-t border-slate-200 bg-slate-50 p-6 lg:border-l lg:border-t-0">
                                <div class="w-full">
                                    <p class="text-sm leading-6 text-slate-600">Complete the application form and upload the required PDF documents.</p>
                                    <a href="<?= portal_url('apply.php?job='.$job->id) ?>" class="mt-5 inline-flex w-full items-center justify-center rounded-xl bg-government-navy px-5 py-3.5 font-bold text-white transition hover:bg-government-blue">
                                        View Details &amp; Apply
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require __DIR__.'/_layout_footer.php'; ?>
