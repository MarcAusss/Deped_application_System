<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Available Job Positions | DepEd Recruitment Portal</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        government: {
                            navy: '#123B6D',
                            blue: '#1D4E89',
                            light: '#EAF2F8',
                            gold: '#D4A017',
                            dark: '#0B2545',
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="min-h-screen bg-slate-50 text-slate-800">

    <div class="bg-government-dark text-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-2 text-xs sm:px-6 lg:px-8">
            <p>Republic of the Philippines</p>

            <p class="hidden text-slate-300 sm:block">
                Department of Education Recruitment Portal
            </p>
        </div>
    </div>

    <header class="border-b border-slate-200 bg-white shadow-sm">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <a
                href="{{ route('jobs.index') }}"
                class="flex items-center gap-4"
            >
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-government-navy text-sm font-black text-white">
                    DepEd
                </div>

                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-government-gold">
                        Department of Education
                    </p>

                    <h1 class="text-xl font-black text-government-dark">
                        Applicant Management System
                    </h1>
                </div>
            </a>
        </div>
    </header>

    <section class="bg-gradient-to-r from-government-dark to-government-blue text-white">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <p class="text-sm font-bold uppercase tracking-widest text-yellow-300">
                Government Career Opportunities
            </p>

            <h2 class="mt-4 max-w-3xl text-4xl font-black leading-tight sm:text-5xl">
                Build your career in public service
            </h2>

            <p class="mt-5 max-w-2xl text-lg leading-8 text-blue-100">
                Explore available positions and submit your application through
                the official recruitment portal.
            </p>
        </div>
    </section>

    <main class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">

        @if(session('success'))
            <div class="mb-8 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-8 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="mb-8 flex flex-col justify-between gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-bold uppercase tracking-widest text-government-blue">
                    Recruitment Opportunities
                </p>

                <h2 class="mt-2 text-3xl font-black text-government-dark">
                    Available Job Positions
                </h2>

                <p class="mt-2 text-slate-600">
                    Select a position to proceed to the online application form.
                </p>
            </div>

            <div class="rounded-xl border border-blue-100 bg-government-light px-5 py-3">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                    Open Positions
                </p>

                <p class="text-2xl font-black text-government-navy">
                    {{ $jobs->count() }}
                </p>
            </div>
        </div>

        @forelse($jobs as $job)
            <article class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">

                <div class="grid lg:grid-cols-[1fr_280px]">

                    <div class="p-6 sm:p-8">
                        <div class="mb-5 flex flex-wrap gap-3">
                            <span class="rounded-full bg-green-50 px-3 py-1.5 text-xs font-bold uppercase text-green-700 ring-1 ring-green-200">
                                Open for Application
                            </span>

                            <span class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold text-government-blue ring-1 ring-blue-200">
                                Government Position
                            </span>
                        </div>

                        <h3 class="text-2xl font-black text-government-dark sm:text-3xl">
                            {{ $job->title }}
                        </h3>

                        <div class="mt-4 h-1 w-20 rounded-full bg-government-gold"></div>

                        <p class="mt-5 whitespace-pre-line leading-7 text-slate-600">
                            {{ $job->description ?: 'No description has been provided for this position.' }}
                        </p>

                        <div class="mt-7 flex flex-wrap gap-x-6 gap-y-1 border-t border-slate-100 pt-5 text-sm text-slate-600">
                            <div>
                                Posted:
                                <span class="font-bold text-slate-800">
                                    {{ optional($job->posted_at ?? $job->created_at)->format('F d, Y') }}
                                </span>
                            </div>

                            @if($job->until)
                                <div>
                                    Until:
                                    <span class="font-bold text-slate-800">
                                        {{ $job->until->format('F d, Y') }}
                                        @if($job->until_time)
                                            {{ \Carbon\Carbon::parse($job->until_time)->format('g:i A') }}
                                        @endif
                                    </span>
                                </div>
                            @endif
                        </div>

                        @if($job->attachment_path || $job->csc_publication_path)
                            <div class="mt-4 flex flex-col items-start gap-2">
                                @if($job->attachment_path)
                                    <a
                                        href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($job->attachment_path) }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="inline-flex items-center gap-2 text-sm font-bold text-government-blue hover:text-government-navy hover:underline"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9.75v6.75m0 0-3-3m3 3 3-3m-8.25 6a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                                        </svg>
                                        View D.M Notice
                                    </a>
                                @endif

                                @if($job->csc_publication_path)
                                    <a
                                        href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($job->csc_publication_path) }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="inline-flex items-center gap-2 text-sm font-bold text-government-blue hover:text-government-navy hover:underline"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9.75v6.75m0 0-3-3m3 3 3-3m-8.25 6a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                                        </svg>
                                        View CSC Publication of Vacancy
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center border-t border-slate-200 bg-slate-50 p-6 lg:border-l lg:border-t-0">
                        <div class="w-full">
                            <p class="text-sm leading-6 text-slate-600">
                                Complete the application form and upload the
                                required PDF documents.
                            </p>

                            <a
                                href="{{ route('apply.form', $job) }}"
                                class="mt-5 inline-flex w-full items-center justify-center rounded-xl bg-government-navy px-5 py-3.5 font-bold text-white transition hover:bg-government-blue"
                            >
                                View Details & Apply
                            </a>
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                <h3 class="text-2xl font-black text-government-dark">
                    No available job positions
                </h3>

                <p class="mt-3 text-slate-500">
                    Please check again for future employment opportunities.
                </p>
            </div>
        @endforelse
    </main>

    <footer class="mt-12 border-t-4 border-government-gold bg-government-dark text-white">
        <div class="mx-auto max-w-7xl px-4 py-8 text-center text-sm text-slate-300 sm:px-6 lg:px-8">
            © {{ date('Y') }} Department of Education. All rights reserved.
        </div>
    </footer>

</body>
</html>