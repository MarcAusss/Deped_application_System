<nav class="flex gap-1 overflow-x-auto border-b border-slate-200 bg-white px-4 py-2 sm:px-6 lg:hidden">
    @if(auth('applicant')->check())
        <a
            href="{{ route('applicant.dashboard') }}"
            class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-bold transition {{ request()->routeIs('applicant.dashboard') ? 'bg-government-light text-government-navy' : 'text-slate-500 hover:text-government-navy' }}"
        >
            Dashboard
        </a>
    @endif

    <a
        href="{{ route('jobs.index') }}"
        class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-bold transition {{ request()->routeIs('jobs.index') ? 'bg-government-light text-government-navy' : 'text-slate-500 hover:text-government-navy' }}"
    >
        Browse Jobs
    </a>

    @if(auth('applicant')->check())
        <a
            href="{{ route('applicant.profile') }}"
            class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-bold transition {{ request()->routeIs('applicant.profile') ? 'bg-government-light text-government-navy' : 'text-slate-500 hover:text-government-navy' }}"
        >
            My Profile
        </a>

        @unless($hideLogout ?? false)
            <form method="POST" action="{{ route('applicant.logout') }}" class="ml-auto">
                @csrf
                <button
                    type="submit"
                    class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-bold text-slate-500 transition hover:text-red-600"
                >
                    Logout
                </button>
            </form>
        @endunless
    @endif
</nav>
