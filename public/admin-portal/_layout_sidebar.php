<?php
if (!defined('PORTAL_BOOTED')) { http_response_code(404); exit; }
$activePage = $activePage ?? '';

function admin_nav_link_class(string $page, string $active): string
{
    return 'sidebar-link flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-bold transition '
        .($page === $active ? 'bg-white/10 text-white' : 'text-indigo-100 hover:bg-white/5 hover:text-white');
}

function admin_nav_group_label(string $label): string
{
    return '<p class="sidebar-label px-3 pb-1 pt-4 text-[11px] font-black uppercase tracking-widest text-indigo-300/70">'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</p>';
}
?>
<aside id="admin-sidebar" class="hidden w-64 shrink-0 flex-col bg-admin-indigoDark text-white transition-all duration-200 lg:sticky lg:top-16 lg:z-20 lg:flex lg:h-[calc(100vh-4rem)] lg:overflow-y-auto">
    <nav class="flex-1 space-y-1 px-3 py-5">
        <a href="<?= admin_url('dashboard.php') ?>" title="Dashboard" class="<?= admin_nav_link_class('dashboard', $activePage) ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5 shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
            </svg>
            <span class="sidebar-label">Dashboard</span>
        </a>

        <?= admin_nav_group_label('Applications') ?>

        <a href="<?= admin_url('applications.php') ?>" title="Applications" class="<?= admin_nav_link_class('applications', $activePage) ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5 shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            <span class="sidebar-label">Applications</span>
        </a>

        <a href="<?= admin_url('approvals.php') ?>" title="Qualified" class="<?= admin_nav_link_class('approvals', $activePage) ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5 shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <span class="sidebar-label">Qualified</span>
        </a>

        <a href="<?= admin_url('archive.php') ?>" title="Disqualified" class="<?= admin_nav_link_class('archive', $activePage) ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5 shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
            </svg>
            <span class="sidebar-label">Disqualified</span>
        </a>

        <?= admin_nav_group_label('Recruitment') ?>

        <a href="<?= admin_url('job-positions.php') ?>" title="Job Positions" class="<?= admin_nav_link_class('job-positions', $activePage) ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5 shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.653v-.443c0-1.09-.542-2.106-1.416-2.72a45.36 45.36 0 0 0-6.984-4.163V4.5A2.25 2.25 0 0 0 12 2.25h0a2.25 2.25 0 0 0-2.25 2.25v1.171a45.36 45.36 0 0 0-6.984 4.163C1.892 10.198 1.35 11.214 1.35 12.304v.443c0 .646.279 1.253.75 1.653" />
            </svg>
            <span class="sidebar-label">Job Positions</span>
        </a>

        <a href="<?= admin_url('job-postings.php') ?>" title="Job Postings" class="<?= admin_nav_link_class('job-postings', $activePage) ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5 shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73s-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46" />
            </svg>
            <span class="sidebar-label">Job Postings</span>
        </a>

        <a href="<?= admin_url('closed-job-postings.php') ?>" title="Closed Postings" class="<?= admin_nav_link_class('closed-job-postings', $activePage) ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5 shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
            </svg>
            <span class="sidebar-label">Closed Postings</span>
        </a>

        <a href="<?= admin_url('publication-of-vacancy.php') ?>" title="Publication of Vacancy" class="<?= admin_nav_link_class('publication-of-vacancy', $activePage) ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5 shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6 9.75h-6m6 3h-6M8.25 21h7.5A2.25 2.25 0 0 0 18 18.75V7.5l-5.25-5.25H6a2.25 2.25 0 0 0-2.25 2.25v14.25a2.25 2.25 0 0 0 2.25 2.25Z" />
            </svg>
            <span class="sidebar-label">Publication of Vacancy</span>
        </a>

        <?= admin_nav_group_label('Accounts') ?>

        <a href="<?= admin_url('users.php') ?>" title="Users" class="<?= admin_nav_link_class('users', $activePage) ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5 shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
            </svg>
            <span class="sidebar-label">Users</span>
        </a>
    </nav>

    <div class="border-t border-white/10 p-3">
        <form method="POST" action="<?= admin_url('logout.php') ?>">
            <?= admin_csrf_field() ?>
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
    <a href="<?= admin_url('dashboard.php') ?>" class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-bold transition <?= $activePage === 'dashboard' ? 'bg-admin-indigoLight text-admin-indigo' : 'text-slate-500 hover:text-admin-indigo' ?>">Dashboard</a>
    <a href="<?= admin_url('applications.php') ?>" class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-bold transition <?= $activePage === 'applications' ? 'bg-admin-indigoLight text-admin-indigo' : 'text-slate-500 hover:text-admin-indigo' ?>">Applications</a>
    <a href="<?= admin_url('approvals.php') ?>" class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-bold transition <?= $activePage === 'approvals' ? 'bg-admin-indigoLight text-admin-indigo' : 'text-slate-500 hover:text-admin-indigo' ?>">Qualified</a>
    <a href="<?= admin_url('archive.php') ?>" class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-bold transition <?= $activePage === 'archive' ? 'bg-admin-indigoLight text-admin-indigo' : 'text-slate-500 hover:text-admin-indigo' ?>">Disqualified</a>
    <a href="<?= admin_url('job-positions.php') ?>" class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-bold transition <?= $activePage === 'job-positions' ? 'bg-admin-indigoLight text-admin-indigo' : 'text-slate-500 hover:text-admin-indigo' ?>">Job Positions</a>
    <a href="<?= admin_url('job-postings.php') ?>" class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-bold transition <?= $activePage === 'job-postings' ? 'bg-admin-indigoLight text-admin-indigo' : 'text-slate-500 hover:text-admin-indigo' ?>">Job Postings</a>
    <a href="<?= admin_url('closed-job-postings.php') ?>" class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-bold transition <?= $activePage === 'closed-job-postings' ? 'bg-admin-indigoLight text-admin-indigo' : 'text-slate-500 hover:text-admin-indigo' ?>">Closed Postings</a>
    <a href="<?= admin_url('publication-of-vacancy.php') ?>" class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-bold transition <?= $activePage === 'publication-of-vacancy' ? 'bg-admin-indigoLight text-admin-indigo' : 'text-slate-500 hover:text-admin-indigo' ?>">Publication</a>
    <a href="<?= admin_url('users.php') ?>" class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-bold transition <?= $activePage === 'users' ? 'bg-admin-indigoLight text-admin-indigo' : 'text-slate-500 hover:text-admin-indigo' ?>">Users</a>
    <form method="POST" action="<?= admin_url('logout.php') ?>" class="ml-auto">
        <?= admin_csrf_field() ?>
        <button type="submit" class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-bold text-slate-500 transition hover:text-red-600">Logout</button>
    </form>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var sidebar = document.getElementById('admin-sidebar');
        var toggle = document.getElementById('sidebar-toggle');
        var icon = document.getElementById('sidebar-toggle-icon');
        if (!sidebar || !toggle) return;
        var STORAGE_KEY = 'adminSidebarCollapsed';

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
