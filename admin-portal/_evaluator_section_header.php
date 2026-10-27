<?php
if (!defined('PORTAL_BOOTED')) { http_response_code(404); exit; }
/**
 * Heading above one admin_split_groups_by_evaluator() section on the
 * Applications / Qualified / Disqualified lists. Expects $evaluatorSection
 * ('admin'|'evaluator') and $sectionGroups in scope.
 */
$isAdminSection = $evaluatorSection === 'admin';
$sectionCount = array_sum(array_map('count', $sectionGroups));
?>
<div class="mb-4 flex flex-wrap items-center gap-3 rounded-xl border px-4 py-3 <?= $isAdminSection ? 'border-amber-200 bg-amber-50' : 'border-admin-indigo/20 bg-admin-indigoLight' ?>">
    <div class="min-w-0 flex-1">
        <h3 class="text-sm font-black uppercase tracking-widest <?= $isAdminSection ? 'text-amber-800' : 'text-admin-indigoDark' ?>">
            <?= $isAdminSection ? 'Evaluation by Admin' : 'Evaluation by Evaluator' ?>
        </h3>
        <p class="mt-0.5 text-xs text-slate-600">
            <?= $isAdminSection
                ? 'Admin accounts evaluate these postings. Evaluator accounts cannot see them.'
                : 'Evaluator accounts evaluate these postings. Admins can view them and make the final decision.' ?>
        </p>
    </div>
    <span class="pill <?= $isAdminSection ? 'bg-amber-100 text-amber-800' : 'bg-white text-admin-indigo' ?>"><?= $sectionCount ?> application(s)</span>
</div>
