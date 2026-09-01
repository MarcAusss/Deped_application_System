<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Applications | DepEd Recruitment Portal</title>

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

<body class="flex min-h-screen flex-col bg-slate-50 text-slate-800">

    @include('applicant.partials.topbar')

    <div class="lg:flex lg:flex-1">
        @include('applicant.partials.sidebar')

        <div class="min-w-0 flex-1">
            @include('applicant.partials.mobile-nav')

            <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

                @if(session('success'))
                    <div class="mb-8 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800">
                        {{ session('success') }}
                    </div>
                @endif

                @php
                    $firstName = str(auth('applicant')->user()->name)->before(' ');
                    $hour = now()->hour;
                    $greeting = match (true) {
                        $hour < 12 => 'Good morning',
                        $hour < 18 => 'Good afternoon',
                        default => 'Good evening',
                    };

                    $pendingCount = $applications->where('status', 'pending')->count();
                    $evaluatedCount = $applications->whereIn('status', ['evaluated', 'excluded'])->count();
                    $qualifiedCount = $applications->where('status', 'qualified')->count();
                @endphp

                <section class="relative overflow-hidden rounded-2xl border border-government-dark bg-gradient-to-br from-government-dark to-government-blue text-white">
                    <div class="grid gap-0 lg:grid-cols-[1.65fr_0.9fr]">
                        <div class="p-6 sm:p-8">
                            <div class="mb-4 flex flex-wrap items-center gap-2">
                                <span class="text-xs font-bold uppercase tracking-widest text-blue-200">
                                    Applicant workspace
                                </span>

                                <span class="inline-flex items-center gap-1.5 rounded-full border border-teal-300/30 bg-teal-900/40 px-2.5 py-1 text-[11px] font-bold text-teal-200">
                                    <span class="h-1.5 w-1.5 rounded-full bg-teal-300"></span>
                                    Application tracking
                                </span>
                            </div>

                            <h1 class="text-2xl font-black leading-tight sm:text-3xl">
                                {{ $greeting }}, {{ $firstName }}.
                            </h1>

                            <p class="mt-3 max-w-xl text-sm leading-relaxed text-blue-100">
                                Track the status of every position you've applied for from one place, and explore new openings when you're ready for your next opportunity.
                            </p>

                            <div class="mt-6 flex flex-wrap gap-3">
                                <a
                                    href="{{ route('jobs.index') }}"
                                    class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-bold text-government-navy transition hover:bg-blue-50"
                                >
                                    Browse open positions
                                </a>
                            </div>
                        </div>

                        <aside class="border-t border-white/15 bg-black/10 p-6 sm:p-8 lg:border-l lg:border-t-0">
                            <span class="text-[11px] font-bold uppercase tracking-widest text-blue-200">
                                Your applications today
                            </span>

                            <p class="mt-1 text-sm font-bold text-white">
                                {{ now()->format('l, d F Y') }}
                            </p>

                            <div class="mt-4 grid grid-cols-3 border-t border-white/15 pt-4">
                                <div>
                                    <strong class="block text-xl font-black text-white">{{ $pendingCount }}</strong>
                                    <span class="text-[11px] font-bold text-blue-200">Pending</span>
                                </div>

                                <div class="border-l border-white/15 pl-3">
                                    <strong class="block text-xl font-black text-white">{{ $evaluatedCount }}</strong>
                                    <span class="text-[11px] font-bold text-blue-200">Evaluated</span>
                                </div>

                                <div class="border-l border-white/15 pl-3">
                                    <strong class="block text-xl font-black text-white">{{ $qualifiedCount }}</strong>
                                    <span class="text-[11px] font-bold text-blue-200">Qualified</span>
                                </div>
                            </div>
                        </aside>
                    </div>
                </section>

                <div class="mb-4 mt-8 border-b border-slate-200 pb-4">
                    <h2 class="text-xl font-black text-government-dark">
                        My Applications
                    </h2>

                    <p class="mt-1 text-sm text-slate-600">
                        Track the status of every position you've applied for.
                    </p>
                </div>

                @php
                    $steps = ['Submitted', 'For Evaluation', 'For Final Qualification'];

                    $statusMeta = [
                        'pending' => [
                            'badge' => 'bg-slate-100 text-slate-600 ring-slate-200',
                            'step' => 2,
                            'tone' => 'neutral',
                            'terminal' => false,
                            'next' => 'Your application is currently under review by our evaluators. You will be updated here once the initial evaluation is complete.',
                        ],
                        'evaluated' => [
                            'badge' => 'bg-blue-50 text-blue-700 ring-blue-200',
                            'step' => 3,
                            'tone' => 'positive',
                            'terminal' => false,
                            'next' => 'Your application has been evaluated and meets the qualification standards. It is now awaiting the final hiring decision.',
                        ],
                        'excluded' => [
                            'badge' => 'bg-red-50 text-red-700 ring-red-200',
                            'step' => 3,
                            'tone' => 'negative',
                            'terminal' => false,
                            'next' => 'Your application did not meet the required qualification standards for this position. No further action is needed.',
                        ],
                        'qualified' => [
                            'badge' => 'bg-green-50 text-green-700 ring-green-200',
                            'step' => 3,
                            'tone' => 'positive',
                            'terminal' => true,
                            'next' => 'Congratulations! Your application has been marked Qualified. Please wait to be contacted regarding the next steps.',
                        ],
                        'disqualified' => [
                            'badge' => 'bg-red-50 text-red-700 ring-red-200',
                            'step' => 3,
                            'tone' => 'negative',
                            'terminal' => true,
                            'next' => 'Your application was not selected for this position. Thank you for taking the time to apply.',
                        ],
                    ];
                @endphp

                @forelse($applications as $application)
                    @php
                        $meta = $statusMeta[$application->status] ?? $statusMeta['pending'];
                        $currentStep = $meta['step'];

                        $stepLabels = [
                            1 => 'Submitted',
                            2 => $currentStep > 2 ? 'Evaluated' : 'For Evaluation',
                            3 => ($meta['terminal'] ?? false) ? ucfirst($application->status) : 'For Final Qualification',
                        ];
                    @endphp

                    <article class="mb-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                @if($application->controlNumber)
                                    <p class="text-xs font-bold uppercase tracking-wide text-government-blue">
                                        Control No: {{ $application->controlNumber->control_number }}
                                    </p>
                                @endif

                                <h3 class="text-lg font-black text-government-dark">
                                    {{ $application->jobPosition->title ?? 'Job position no longer available' }}
                                </h3>

                                <p class="mt-1 text-sm text-slate-500">
                                    Applied on {{ $application->created_at->format('F d, Y') }}
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <span class="inline-flex w-fit items-center rounded-full px-4 py-1.5 text-xs font-bold uppercase ring-1 {{ $meta['badge'] }}">
                                    {{ ucfirst($application->status) }}
                                </span>

                                @if($application->status === 'pending')
                                    <a
                                        href="{{ route('applicant.applications.edit', $application) }}"
                                        class="inline-flex w-fit items-center gap-1.5 rounded-full border border-government-navy px-4 py-1.5 text-xs font-bold text-government-navy transition hover:bg-government-navy hover:text-white"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-3.5 w-3.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                        Edit
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="mt-6 flex items-center justify-center">
                            @foreach($steps as $index => $step)
                                @php
                                    $stepNumber = $index + 1;
                                    $isDone = $stepNumber < $currentStep;
                                    $isCurrent = $stepNumber === $currentStep;
                                    $isTerminalStep = $isCurrent && ($meta['terminal'] ?? false);
                                    $isFinalNegative = $isTerminalStep && $meta['tone'] === 'negative';
                                    $isFinalPositive = $isTerminalStep && $meta['tone'] === 'positive';

                                    $circleStyle = match(true) {
                                        $isFinalNegative => 'border-red-600 bg-red-600 text-white',
                                        $isFinalPositive => 'border-green-600 bg-green-600 text-white',
                                        $isCurrent && $meta['tone'] === 'negative' => 'border-red-600 bg-red-600 text-white',
                                        $isCurrent => 'border-government-navy bg-government-navy text-white',
                                        $isDone => 'border-government-navy bg-white text-government-navy',
                                        default => 'border-slate-300 bg-white text-slate-400',
                                    };

                                    $labelStyle = $isCurrent
                                        ? ($meta['tone'] === 'negative' ? 'text-red-600' : 'text-government-navy')
                                        : ($isDone ? 'text-slate-600' : 'text-slate-400');

                                    $lineStyle = 'bg-government-blue';
                                @endphp

                                <div class="flex flex-col items-center text-center">
                                    <div class="flex items-center">
                                        <div class="h-0.5 w-10 sm:w-16 {{ $index === 0 ? 'invisible' : $lineStyle }}"></div>

                                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border-2 text-xs font-bold {{ $circleStyle }}">
                                            @if($isDone)
                                                &check;
                                            @else
                                                {{ $stepNumber }}
                                            @endif
                                        </div>

                                        <div class="h-0.5 w-10 sm:w-16 {{ $stepNumber === count($steps) ? 'invisible' : $lineStyle }}"></div>
                                    </div>

                                    <p class="mt-2 whitespace-nowrap text-[11px] font-bold uppercase tracking-wide {{ $labelStyle }}">
                                        {{ $stepLabels[$stepNumber] }}
                                    </p>
                                </div>
                            @endforeach
                        </div>

                        @php
                            $evaluation = $application->evaluation;
                            $qsResult = $evaluation?->result ?? \App\Models\ApplicationEvaluation::RESULT_PENDING_DOCUMENT_REVIEW;

                            $qsStyles = [
                                \App\Models\ApplicationEvaluation::RESULT_QUALIFIED => [
                                    'label' => 'Meet the QS',
                                    'badge' => 'bg-green-50 text-green-700 ring-green-200',
                                ],
                                \App\Models\ApplicationEvaluation::RESULT_NOT_QUALIFIED => [
                                    'label' => 'Did not Meet the QS',
                                    'badge' => 'bg-red-50 text-red-700 ring-red-200',
                                ],
                                \App\Models\ApplicationEvaluation::RESULT_EXCLUDED => [
                                    'label' => 'Did not Meet the QS',
                                    'badge' => 'bg-red-50 text-red-700 ring-red-200',
                                ],
                            ];

                            $qsStyle = $qsStyles[$qsResult] ?? [
                                'label' => 'Pending',
                                'badge' => 'bg-slate-100 text-slate-600 ring-slate-200',
                            ];

                            $qsReason = null;

                            if (
                                in_array($qsResult, [
                                    \App\Models\ApplicationEvaluation::RESULT_NOT_QUALIFIED,
                                    \App\Models\ApplicationEvaluation::RESULT_EXCLUDED,
                                ], true)
                                && $evaluation
                            ) {
                                $disqualifiedCategories = \App\Support\EvaluationChecklist::disqualifiedCategories(
                                    $evaluation->qs_education_met,
                                    $evaluation->qs_experience_met,
                                    $evaluation->qs_training_met,
                                    $evaluation->qs_eligibility_met,
                                );

                                if ($disqualifiedCategories !== []) {
                                    $qsReason = 'Did not Meet the QS: ' . implode(', ', $disqualifiedCategories) . '.';
                                }
                            }
                        @endphp

                        @if($qsResult !== \App\Models\ApplicationEvaluation::RESULT_PENDING_DOCUMENT_REVIEW)
                            <div class="mt-5 rounded-xl border border-slate-200 bg-white p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                                        Qualification Standards
                                    </p>

                                    <span class="inline-flex w-fit items-center rounded-full px-3 py-1 text-xs font-bold uppercase ring-1 {{ $qsStyle['badge'] }}">
                                        {{ $qsStyle['label'] }}
                                    </span>
                                </div>

                                @if($qsReason)
                                    <p class="mt-2 text-sm text-slate-700">
                                        {{ $qsReason }}
                                    </p>
                                @endif
                            </div>
                        @endif

                        @if(filled($evaluation?->remarks))
                            <div class="mt-3 rounded-xl border border-slate-200 bg-white p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                                    Evaluator's Remarks
                                </p>

                                <p class="mt-1 text-sm text-slate-700">
                                    {{ $evaluation->remarks }}
                                </p>
                            </div>
                        @endif

                        <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                                What's next
                            </p>

                            <p class="mt-1 text-sm text-slate-700">
                                {{ $meta['next'] }}
                            </p>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                        <h3 class="text-2xl font-black text-government-dark">
                            You haven't applied to any positions yet
                        </h3>

                        <p class="mt-3 text-slate-500">
                            Browse open job positions and submit your application.
                        </p>

                        <a
                            href="{{ route('jobs.index') }}"
                            class="mt-6 inline-flex items-center justify-center rounded-xl bg-government-navy px-5 py-3 font-bold text-white transition hover:bg-government-blue"
                        >
                            View Open Positions
                        </a>
                    </div>
                @endforelse
            </main>
        </div>
    </div>

    <footer class="border-t-4 border-government-gold bg-government-dark text-white">
        <div class="px-4 py-6 text-center text-sm text-slate-300 sm:px-6 lg:px-8">
            © {{ date('Y') }} Department of Education. All rights reserved.
        </div>
    </footer>

</body>
</html>
