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
            <div class="relative ml-auto">
                <button type="button" id="user-menu-button" class="flex items-center gap-3 rounded-full transition hover:opacity-80" aria-haspopup="true" aria-expanded="false">
                    <span class="hidden text-sm font-bold text-slate-700 sm:inline"><?= evaluator_e($__user->name) ?></span>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-evaluator-indigo text-sm font-black text-white">
                        <?= evaluator_e(evaluator_initials($__user->name) ?: '?') ?>
                    </span>
                </button>

                <div id="user-menu-panel" class="hidden absolute right-0 top-[calc(100%+0.5rem)] w-56 overflow-hidden rounded-xl border border-slate-200 bg-white py-1.5 shadow-lg">
                    <div class="flex items-center gap-2.5 px-3 py-2.5 text-sm font-bold text-slate-700">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5 shrink-0 text-slate-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        <?= evaluator_e($__user->name) ?>
                    </div>
                    <div class="border-t border-slate-100"></div>
                    <form method="POST" action="<?= evaluator_url('logout.php') ?>">
                        <?= evaluator_csrf_field() ?>
                        <button type="submit" class="flex w-full items-center gap-2.5 px-3 py-2.5 text-left text-sm font-bold text-slate-600 transition hover:bg-slate-50 hover:text-red-600">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5 shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                            </svg>
                            Sign out
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var button = document.getElementById('user-menu-button');
        var panel = document.getElementById('user-menu-panel');
        if (!button || !panel) return;

        var close = function () {
            panel.classList.add('hidden');
            button.setAttribute('aria-expanded', 'false');
        };

        button.addEventListener('click', function (e) {
            e.stopPropagation();
            var willOpen = panel.classList.contains('hidden');
            panel.classList.toggle('hidden', !willOpen);
            button.setAttribute('aria-expanded', String(willOpen));
        });

        document.addEventListener('click', function (e) {
            if (!panel.contains(e.target) && !button.contains(e.target)) close();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') close();
        });
    });
</script>
