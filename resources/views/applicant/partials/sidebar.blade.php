<aside id="applicant-sidebar" class="hidden w-64 shrink-0 flex-col bg-government-dark text-white transition-all duration-200 lg:flex">
    <nav class="flex-1 space-y-1 px-3 py-5">
        @if(auth('applicant')->check())
            <a
                href="{{ route('applicant.dashboard') }}"
                title="Dashboard"
                class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-bold transition {{ request()->routeIs('applicant.dashboard') ? 'bg-white/10 text-white' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3.75m8.5-3.75 1 3.75m0 0 .5 2.25m-.5-2.25h-8m-.5 2.25h9m-9 0-.5 0" />
                </svg>
                <span class="sidebar-label">Dashboard</span>
            </a>
        @endif

        <a
            href="{{ route('jobs.index') }}"
            title="Browse Jobs"
            class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-bold transition {{ request()->routeIs('jobs.index') ? 'bg-white/10 text-white' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5 shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21" />
            </svg>
            <span class="sidebar-label">Browse Jobs</span>
        </a>

        @if(auth('applicant')->check())
            <a
                href="{{ route('applicant.profile') }}"
                title="My Profile"
                class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-bold transition {{ request()->routeIs('applicant.profile') ? 'bg-white/10 text-white' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                <span class="sidebar-label">My Profile</span>
            </a>
        @else
            <a
                href="{{ route('applicant.login') }}"
                title="Login"
                class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-bold transition {{ request()->routeIs('applicant.login') ? 'bg-white/10 text-white' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}"
            >
                <span class="sidebar-label">Login</span>
            </a>

            <a
                href="{{ route('applicant.register') }}"
                title="Register"
                class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-bold transition {{ request()->routeIs('applicant.register') ? 'bg-white/10 text-white' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}"
            >
                <span class="sidebar-label">Register</span>
            </a>
        @endif
    </nav>

    @if(auth('applicant')->check())
        <div class="border-t border-white/10 p-3">
            <form method="POST" action="{{ route('applicant.logout') }}">
                @csrf
                <button
                    type="submit"
                    title="Logout"
                    class="sidebar-link flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-bold text-blue-100 transition hover:bg-white/5 hover:text-white"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                    </svg>
                    <span class="sidebar-label">Logout</span>
                </button>
            </form>
        </div>
    @endif
</aside>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var sidebar = document.getElementById('applicant-sidebar');
        var toggle = document.getElementById('sidebar-toggle');
        var icon = document.getElementById('sidebar-toggle-icon');
        var STORAGE_KEY = 'applicantSidebarCollapsed';

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
        try {
            stored = localStorage.getItem(STORAGE_KEY) === 'true';
        } catch (e) {}

        applyState(stored);

        toggle.addEventListener('click', function () {
            var next = !sidebar.classList.contains('w-20');
            applyState(next);

            try {
                localStorage.setItem(STORAGE_KEY, String(next));
            } catch (e) {}
        });
    });
</script>
