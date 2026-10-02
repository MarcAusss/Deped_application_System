<?php
if (!defined('PORTAL_BOOTED')) { http_response_code(404); exit; }
$activePage = $activePage ?? '';

function eval_nav_link_class(string $page, string $active): string
{
    return 'sidebar-link flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-bold transition '
        .($page === $active ? 'bg-white/10 text-white' : 'text-indigo-100 hover:bg-white/5 hover:text-white');
}
?>
<aside id="evaluator-sidebar" class="hidden w-64 shrink-0 flex-col bg-evaluator-indigoDark text-white transition-all duration-200 lg:sticky lg:top-16 lg:z-20 lg:flex lg:h-[calc(100vh-4rem)] lg:overflow-y-auto">
    <nav class="flex-1 space-y-1 px-3 py-5">
        <a href="<?= evaluator_url('applications.php') ?>" title="Applications" class="<?= eval_nav_link_class('applications', $activePage) ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5 shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            <span class="sidebar-label">Applications</span>
        </a>

        <a href="<?= evaluator_url('qualified.php') ?>" title="Qualified" class="<?= eval_nav_link_class('qualified', $activePage) ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5 shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <span class="sidebar-label">Qualified</span>
        </a>

        <a href="<?= evaluator_url('disqualified.php') ?>" title="Disqualified" class="<?= eval_nav_link_class('disqualified', $activePage) ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5 shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
            </svg>
            <span class="sidebar-label">Disqualified</span>
        </a>
    </nav>

    <div class="border-t border-white/10 p-3">
        <form method="POST" action="<?= evaluator_url('logout.php') ?>">
            <?= evaluator_csrf_field() ?>
            <button type="submit" title="Logout" class="sidebar-link flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-bold text-indigo-100 transition hover:bg-white/5 hover:text-white">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                </svg>
                <span class="sidebar-label">Logout</span>
            </button>
        </form>
    </div>
</aside>

<nav class="flex gap-1 overflow-x-auto border-b border-slate-200 bg-white px-4 py-2 sm:px-6 lg:hidden">
    <a href="<?= evaluator_url('applications.php') ?>" class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-bold transition <?= $activePage === 'applications' ? 'bg-evaluator-indigoLight text-evaluator-indigo' : 'text-slate-500 hover:text-evaluator-indigo' ?>">Applications</a>
    <a href="<?= evaluator_url('qualified.php') ?>" class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-bold transition <?= $activePage === 'qualified' ? 'bg-evaluator-indigoLight text-evaluator-indigo' : 'text-slate-500 hover:text-evaluator-indigo' ?>">Qualified</a>
    <a href="<?= evaluator_url('disqualified.php') ?>" class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-bold transition <?= $activePage === 'disqualified' ? 'bg-evaluator-indigoLight text-evaluator-indigo' : 'text-slate-500 hover:text-evaluator-indigo' ?>">Disqualified</a>
    <form method="POST" action="<?= evaluator_url('logout.php') ?>" class="ml-auto">
        <?= evaluator_csrf_field() ?>
        <button type="submit" class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-bold text-slate-500 transition hover:text-red-600">Logout</button>
    </form>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var sidebar = document.getElementById('evaluator-sidebar');
        var toggle = document.getElementById('sidebar-toggle');
        var icon = document.getElementById('sidebar-toggle-icon');
        if (!sidebar || !toggle) return;
        var STORAGE_KEY = 'evaluatorSidebarCollapsed';

        function applyState(collapsed) {
            if (collapsed) {
                sidebar.classList.remove('w-64');
                sidebar.classList.add('w-20');
                sidebar.querySelectorAll('.sidebar-label').forEach(function (el) { el.classList.add('hidden'); });
                sidebar.querySelectorAll('.sidebar-link').forEach(function (el) { el.classList.add('justify-center'); });
                icon.style.transform = 'rotate(180deg)';
                toggle.setAttribute('aria-label', 'Expand sidebar');
            } else {
                sidebar.classList.remove('w-20');
                sidebar.classList.add('w-64');
                sidebar.querySelectorAll('.sidebar-label').forEach(function (el) { el.classList.remove('hidden'); });
                sidebar.querySelectorAll('.sidebar-link').forEach(function (el) { el.classList.remove('justify-center'); });
                icon.style.transform = 'rotate(0deg)';
                toggle.setAttribute('aria-label', 'Collapse sidebar');
            }
        }

        var stored = false;
        try { stored = localStorage.getItem(STORAGE_KEY) === 'true'; } catch (e) {}

        applyState(stored);

        toggle.addEventListener('click', function () {
            var next = !sidebar.classList.contains('w-20');
            applyState(next);
            try { localStorage.setItem(STORAGE_KEY, String(next)); } catch (e) {}
        });
    });
</script>
