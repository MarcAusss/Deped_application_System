<?php
if (!defined('PORTAL_BOOTED')) { http_response_code(404); exit; }
$__user = evaluator_user();
?>
<div class="sticky top-0 z-30 border-b border-slate-200 bg-white">
    <div class="flex min-h-16 items-center justify-between px-4 py-2 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3 lg:hidden">
            <img src="/images/Department_of_Education_(DepEd).svg.webp" alt="Department of Education" class="h-9 w-9 shrink-0 object-contain">
            <p class="text-sm font-black uppercase tracking-wide text-evaluator-indigoDark">Evaluation Desk</p>
        </div>

        <div class="hidden items-center gap-3 lg:flex">
            <button type="button" id="sidebar-toggle" aria-label="Collapse sidebar" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-800">
                <svg id="sidebar-toggle-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4 transition-transform duration-200">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                </svg>
            </button>

            <img src="/images/Department_of_Education_(DepEd).svg.webp" alt="Department of Education" class="h-9 w-9 shrink-0 object-contain">

            <div class="min-w-0">
                <p class="truncate text-[11px] font-bold uppercase tracking-wide text-slate-500">Department of Education</p>
                <p class="truncate text-sm font-black uppercase tracking-wide text-slate-800">
                    SDO Albay CARES
                    <span class="font-bold normal-case text-slate-400">&middot;</span>
                    <span class="font-bold text-evaluator-indigo">Evaluation Desk</span>
                </p>
            </div>
        </div>

        <?php if ($__user): ?>
            <div class="ml-auto flex items-center gap-3">
                <span class="hidden text-sm font-bold text-slate-700 sm:inline"><?= evaluator_e($__user->name) ?></span>
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-evaluator-indigo text-sm font-black text-white">
                    <?= evaluator_e(evaluator_initials($__user->name) ?: '?') ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
