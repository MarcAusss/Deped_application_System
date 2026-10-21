<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';
require __DIR__.'/_ier.php';

evaluator_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
csrf_verify();

$ids = eval_ier_resolve_ids();
$groups = eval_ier_load_groups($ids);
$totalApplications = count($ids);

$pageTitle = 'IER Preview | Evaluation Desk';
require __DIR__.'/_layout_head.php';
?>

<?php $activePage = ''; require __DIR__.'/_layout_topbar.php'; ?>

<div class="lg:flex lg:flex-1">
    <?php require __DIR__.'/_layout_sidebar.php'; ?>

    <div class="min-w-0 flex-1">
        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <a href="javascript:history.back()" class="mb-4 inline-flex items-center gap-1.5 text-sm font-bold text-slate-500 hover:text-evaluator-indigo">&larr; Back</a>

            <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-sky-200 bg-sky-50 p-4">
                <div class="text-sm text-sky-800"><strong><?= number_format($totalApplications) ?></strong> application(s) will be included in the Excel file.</div>
                <div class="flex flex-wrap gap-2">
                    <span class="rounded-full border border-sky-300 bg-white px-3 py-1.5 text-xs font-bold text-sky-700">A3 Landscape</span>
                    <span class="rounded-full border border-sky-300 bg-white px-3 py-1.5 text-xs font-bold text-sky-700"><?= count($groups) ?> Worksheet(s)</span>
                </div>
            </div>

            <?php if (empty($groups)): ?>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-center text-amber-800">No applications match. The exported IER will contain an empty table.</div>
            <?php endif; ?>

            <?php foreach ($groups as $group):
                $summary = eval_ier_position_summary($group['position']);
                $total = count($group['applications']);
                $previewRows = array_slice($group['applications'], 0, 10);
            ?>
                <article class="mb-8 rounded-lg border border-slate-300 bg-white p-6 shadow-lg">
                    <h3 class="mb-4 text-center text-lg font-black text-evaluator-indigoDark">INITIAL EVALUATION RESULT (IER)</h3>

                    <table class="mb-4 w-full table-fixed border-collapse text-xs">
                        <tr>
                            <th class="border border-sky-300 bg-sky-50 p-2 text-left font-bold text-sky-800">Position</th>
                            <td class="border border-sky-300 p-2"><?= evaluator_e($summary['position'] ?: '—') ?></td>
                            <th class="border border-sky-300 bg-sky-50 p-2 text-left font-bold text-sky-800">Salary Grade and Monthly Salary</th>
                            <td class="border border-sky-300 p-2"><?= evaluator_e($summary['salary'] ?: '—') ?></td>
                        </tr>
                        <tr><th colspan="4" class="border border-sky-800 bg-sky-600 p-2 text-left font-bold uppercase text-white">Qualification Standards</th></tr>
                        <tr>
                            <th class="border border-sky-300 bg-sky-50 p-2 text-left font-bold text-sky-800">Education</th>
                            <td class="border border-sky-300 p-2"><?= evaluator_e($summary['education_requirement'] ?: '—') ?></td>
                            <th class="border border-sky-300 bg-sky-50 p-2 text-left font-bold text-sky-800">Training</th>
                            <td class="border border-sky-300 p-2"><?= evaluator_e($summary['training_requirement'] ?: '—') ?></td>
                        </tr>
                        <tr>
                            <th class="border border-sky-300 bg-sky-50 p-2 text-left font-bold text-sky-800">Experience</th>
                            <td class="border border-sky-300 p-2"><?= evaluator_e($summary['experience_requirement'] ?: '—') ?></td>
                            <th class="border border-sky-300 bg-sky-50 p-2 text-left font-bold text-sky-800">Eligibility</th>
                            <td class="border border-sky-300 p-2"><?= evaluator_e($summary['eligibility_requirement'] ?: '—') ?></td>
                        </tr>
                    </table>

                    <div class="overflow-x-auto border-2 border-sky-800">
                        <table class="w-full min-w-[1400px] border-collapse text-[11px]">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="border border-sky-800 bg-sky-600 p-2 text-white">No.</th>
                                    <th rowspan="2" class="border border-sky-800 bg-sky-600 p-2 text-white">Application Code</th>
                                    <th rowspan="2" class="border border-sky-800 bg-sky-600 p-2 text-white">Name</th>
                                    <th colspan="9" class="border border-sky-800 bg-sky-600 p-2 text-white">Personal Information</th>
                                    <th rowspan="2" class="border border-sky-800 bg-sky-600 p-2 text-white">Education</th>
                                    <th colspan="2" class="border border-sky-800 bg-sky-600 p-2 text-white">Training</th>
                                    <th colspan="2" class="border border-sky-800 bg-sky-600 p-2 text-white">Experience</th>
                                    <th rowspan="2" class="border border-sky-800 bg-sky-600 p-2 text-white">Eligibility</th>
                                    <th rowspan="2" class="border border-sky-800 bg-sky-600 p-2 text-white">Remarks</th>
                                </tr>
                                <tr>
                                    <?php foreach (['Address', 'Age', 'Sex', 'Civil Status', 'Religion', 'Disability', 'Ethnic Group', 'Email', 'Contact'] as $h): ?>
                                        <th class="border border-sky-800 bg-sky-500 p-1.5 text-white"><?= $h ?></th>
                                    <?php endforeach; ?>
                                    <th class="border border-sky-800 bg-sky-500 p-1.5 text-white">Title</th>
                                    <th class="border border-sky-800 bg-sky-500 p-1.5 text-white">Hours</th>
                                    <th class="border border-sky-800 bg-sky-500 p-1.5 text-white">Details</th>
                                    <th class="border border-sky-800 bg-sky-500 p-1.5 text-white">Years</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($previewRows as $i => $app):
                                    $row = eval_ier_row($i + 1, $app->_profile, $app->_educations, $app->_trainings, $app->_experiences, $app->_eligibilities, $app->_control_number, $app->_evaluation, $app->status);
                                ?>
                                    <tr class="<?= $i % 2 === 1 ? 'bg-slate-50' : 'bg-white' ?>">
                                        <td class="border border-slate-400 p-1.5 text-center"><?= $row['number'] ?></td>
                                        <td class="border border-slate-400 p-1.5 text-center"><?= evaluator_e($row['application_code']) ?></td>
                                        <td class="border border-slate-400 p-1.5 font-bold"><?= evaluator_e($row['name']) ?></td>
                                        <td class="border border-slate-400 p-1.5 whitespace-pre-line"><?= evaluator_e($row['address']) ?></td>
                                        <td class="border border-slate-400 p-1.5 text-center"><?= evaluator_e((string) ($row['age'] ?? '—')) ?></td>
                                        <td class="border border-slate-400 p-1.5 text-center"><?= evaluator_e($row['sex']) ?></td>
                                        <td class="border border-slate-400 p-1.5 text-center"><?= evaluator_e($row['civil_status']) ?></td>
                                        <td class="border border-slate-400 p-1.5 text-center"><?= evaluator_e($row['religion']) ?></td>
                                        <td class="border border-slate-400 p-1.5 text-center"><?= evaluator_e($row['disability']) ?></td>
                                        <td class="border border-slate-400 p-1.5 text-center"><?= evaluator_e($row['ethnic_group']) ?></td>
                                        <td class="border border-slate-400 p-1.5 whitespace-pre-line"><?= evaluator_e($row['email']) ?></td>
                                        <td class="border border-slate-400 p-1.5 text-center"><?= evaluator_e($row['contact_number']) ?></td>
                                        <td class="border border-slate-400 p-1.5 whitespace-pre-line"><?= evaluator_e($row['education']) ?></td>
                                        <td class="border border-slate-400 p-1.5 whitespace-pre-line"><?= evaluator_e($row['training_title']) ?></td>
                                        <td class="border border-slate-400 p-1.5 text-center"><?= evaluator_e((string) ($row['training_hours'] ?? '—')) ?></td>
                                        <td class="border border-slate-400 p-1.5 whitespace-pre-line"><?= evaluator_e($row['experience_details']) ?></td>
                                        <td class="border border-slate-400 p-1.5 text-center whitespace-pre-line"><?= evaluator_e($row['experience_years']) ?></td>
                                        <td class="border border-slate-400 p-1.5 whitespace-pre-line"><?= evaluator_e($row['eligibility']) ?></td>
                                        <td class="border border-slate-400 p-1.5 whitespace-pre-line"><?= evaluator_e($row['remarks']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-2 flex items-center justify-between text-xs text-slate-500">
                        <span>Position: <strong class="text-sky-700"><?= evaluator_e($summary['position'] ?: 'Unassigned') ?></strong></span>
                        <span>Previewing <strong class="text-sky-700"><?= count($previewRows) ?></strong> of <strong class="text-sky-700"><?= $total ?></strong> record(s)</span>
                    </div>
                </article>
            <?php endforeach; ?>

            <form method="POST" action="<?= evaluator_url('ier-export.php') ?>" class="sticky bottom-4 mt-6 flex justify-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-lg">
                <?= evaluator_csrf_field() ?>
                <?php foreach ($ids as $id): ?><input type="hidden" name="ids[]" value="<?= $id ?>"><?php endforeach; ?>
                <a href="javascript:history.back()" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-50">Cancel</a>
                <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-bold text-white hover:bg-emerald-700">Export Excel</button>
            </form>
        </main>
    </div>
</div>

<?php require __DIR__.'/_layout_footer.php'; ?>
