<?php
if (!defined('PORTAL_BOOTED')) { http_response_code(404); exit; }
$activePage = $activePage ?? '';

function eval_nav_link_class(string $page, string $active): string
{
    return 'sidebar-link flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-bold transition '
        .($page === $active ? 'bg-evaluator-indigoLight text-evaluator-indigo' : 'text-white/80 hover:bg-white/5 hover:text-white');
}
?>
<aside id="evaluator-sidebar" class="hidden w-64 shrink-0 flex-col bg-evaluator-indigoDark text-white transition-all duration-200 lg:sticky lg:top-16 lg:z-20 lg:flex lg:h-[calc(100vh-4rem)] lg:overflow-y-auto">
    <nav class="flex-1 space-y-1 px-3 py-5">
        <a href="<?= evaluator_url('dashboard.php') ?>" title="Evaluation Workspace" class="sidebar-link mb-3 flex items-center gap-3 rounded-full border border-l-4 border-slate-200 border-l-evaluator-indigo bg-white px-4 py-2.5 text-sm font-bold text-evaluator-indigo shadow-sm transition hover:shadow-md">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5 shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>
            <span class="sidebar-label">Evaluation Workspace</span>
        </a>

        <a href="<?= evaluator_url('applications.php') ?>" title="Applications" class="<?= eval_nav_link_class('applications', $activePage) ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5 shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            <span class="sidebar-label">Applications</span>
        </a>

        <a href="<?= evaluator_url('evaluated.php') ?>" title="Evaluated" class="<?= eval_nav_link_class('evaluated', $activePage) ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5 shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.03 0 1.9.693 2.166 1.638m-5.8 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664M6.75 7.5h10.5a2.25 2.25 0 0 1 2.25 2.25v10.5a2.25 2.25 0 0 1-2.25 2.25H6.75a2.25 2.25 0 0 1-2.25-2.25V9.75A2.25 2.25 0 0 1 6.75 7.5Z" />
            </svg>
            <span class="sidebar-label">Evaluated</span>
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
</aside>

<nav class="flex gap-1 overflow-x-auto border-b border-slate-200 bg-white px-4 py-2 sm:px-6 lg:hidden">
    <a href="<?= evaluator_url('dashboard.php') ?>" class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-bold transition <?= $activePage === 'dashboard' ? 'bg-evaluator-indigoLight text-evaluator-indigo' : 'text-slate-500 hover:text-evaluator-indigo' ?>">Evaluation Workspace</a>
    <a href="<?= evaluator_url('applications.php') ?>" class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-bold transition <?= $activePage === 'applications' ? 'bg-evaluator-indigoLight text-evaluator-indigo' : 'text-slate-500 hover:text-evaluator-indigo' ?>">Applications</a>
    <a href="<?= evaluator_url('evaluated.php') ?>" class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-bold transition <?= $activePage === 'evaluated' ? 'bg-evaluator-indigoLight text-evaluator-indigo' : 'text-slate-500 hover:text-evaluator-indigo' ?>">Evaluated</a>
    <a href="<?= evaluator_url('qualified.php') ?>" class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-bold transition <?= $activePage === 'qualified' ? 'bg-evaluator-indigoLight text-evaluator-indigo' : 'text-slate-500 hover:text-evaluator-indigo' ?>">Qualified</a>
    <a href="<?= evaluator_url('disqualified.php') ?>" class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-bold transition <?= $activePage === 'disqualified' ? 'bg-evaluator-indigoLight text-evaluator-indigo' : 'text-slate-500 hover:text-evaluator-indigo' ?>">Disqualified</a>
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
