<div class="sticky top-0 z-30 border-b border-slate-200 bg-white">
    <div class="flex min-h-16 items-center justify-between px-4 py-2 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3 lg:hidden">
            <img
                src="{{ url('images/Department_of_Education_(DepEd).svg.webp') }}"
                alt="Department of Education"
                class="h-9 w-9 shrink-0 object-contain"
            >

            <p class="text-sm font-black uppercase tracking-wide text-government-dark">
                SDO Albay CARES
            </p>
        </div>

        <div class="hidden items-center gap-3 lg:flex">
            <button
                type="button"
                id="sidebar-toggle"
                aria-label="Collapse sidebar"
                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-800"
            >
                <svg id="sidebar-toggle-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4 transition-transform duration-200">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                </svg>
            </button>

            <img
                src="{{ url('images/Department_of_Education_(DepEd).svg.webp') }}"
                alt="Department of Education"
                class="h-9 w-9 shrink-0 object-contain"
            >

            <div class="min-w-0">
                <p class="truncate text-[11px] font-bold uppercase tracking-wide text-slate-500">
                    Department of Education
                </p>

                <p class="truncate text-sm font-black uppercase tracking-wide text-slate-800">
                    SDO Albay CARES
                    <span class="font-bold normal-case text-slate-400">&middot;</span>
                    <span class="font-bold text-slate-800">Recruitment Portal</span>
                </p>

                <a
                    href="mailto:cares.support@depedalbay.com"
                    class="mt-0.5 inline-flex items-center gap-2 rounded-md bg-government-light px-2.5 py-1.5 text-sm font-bold normal-case text-government-navy transition hover:bg-blue-100"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="h-4 w-4 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                    <span class="truncate">For issues &amp; concerns: cares.support@depedalbay.com</span>
                </a>
            </div>
        </div>

        @if(auth('applicant')->check())
            @php
                $applicantName = auth('applicant')->user()->name;
                $initials = collect(explode(' ', $applicantName))
                    ->filter()
                    ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                    ->take(2)
                    ->implode('');
            @endphp

            <div class="ml-auto flex items-center gap-3">
                <span class="hidden text-sm font-bold text-slate-700 sm:inline">
                    {{ $applicantName }}
                </span>

                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-government-navy text-sm font-black text-white">
                    {{ $initials ?: '?' }}
                </div>
            </div>
        @else
            <div class="ml-auto flex items-center gap-2 text-sm">
                <a
                    href="{{ route('applicant.login') }}"
                    class="rounded-lg border border-government-navy px-3 py-1.5 font-bold text-government-navy transition hover:bg-government-navy hover:text-white"
                >
                    Login
                </a>

                <a
                    href="{{ route('applicant.register') }}"
                    class="rounded-lg bg-government-navy px-3 py-1.5 font-bold text-white transition hover:bg-government-blue"
                >
                    Register
                </a>
            </div>
        @endif
    </div>
</div>
