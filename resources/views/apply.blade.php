<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>{{ $application ? 'Edit Application' : 'Apply' }} for {{ $job->title }}</title>

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
    @include('partials.desktop-scale')
</head>

<body class="flex min-h-screen flex-col bg-slate-50 text-slate-800">

    @include('applicant.partials.topbar')

    <section class="relative overflow-hidden bg-gradient-to-r from-government-dark to-government-blue text-white">
        <div class="mx-auto flex max-w-5xl flex-col items-center justify-center gap-2 px-4 py-12 sm:flex-row sm:px-6 lg:-translate-x-20 lg:px-8">
            <img
                src="{{ url('images/depedalbay.png') }}"
                alt="DepEd Division of Albay"
                class="h-[8.75rem] w-[8.75rem] shrink-0 object-contain"
            >

            <div class="text-justify">
                <p class="text-base font-bold uppercase tracking-widest text-white">
                    Welcome!
                </p>

                <h2 class="mt-1 font-black">
                    <span class="text-2xl sm:text-3xl">SDO ALBAY CARES</span>
                    <br>
                    <span class="text-l g sm:text-xl">(Career Application & <br>Recruitment for Education Services)</span>
                </h2>

                <p class="mt-1 text-lg text-blue-100">
                    Applying for:
                    <span class="font-black text-white">
                        {{ $job->title }}
                    </span>
                </p>
            </div>
        </div>

        @if(filled($job->attachment_paths) || filled($job->csc_publication_paths))
            <div class="mx-4 mb-4 flex flex-col items-start gap-2 sm:mx-6 sm:mb-6 lg:absolute lg:bottom-6 lg:left-8 lg:mx-0 lg:mb-0">
                <div class="flex flex-col items-start gap-3 sm:flex-row sm:flex-wrap sm:gap-6">
                    @foreach(($job->attachment_paths ?? []) as $index => $path)
                        <a
                            href="{{ route('public-file', $path) }}"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-white/30 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-white/10"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9.75v6.75m0 0-3-3m3 3 3-3m-8.25 6a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                            </svg>
                             D.M Notice{{ count($job->attachment_paths) > 1 ? ' ' . ($index + 1) : '' }}
                        </a>
                    @endforeach

                    @foreach(($job->csc_publication_paths ?? []) as $index => $path)
                        <a
                            href="{{ route('public-file', $path) }}"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-white/30 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-white/10"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9.75v6.75m0 0-3-3m3 3 3-3m-8.25 6a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                            </svg>
                             CSC Publication of Vacancy{{ count($job->csc_publication_paths) > 1 ? ' ' . ($index + 1) : '' }}
                        </a>
                    @endforeach
                </div>

                <p class="whitespace-nowrap text-xs text-blue-100">
                    Important: Review the D.M. Notice and CSC Publication of Vacancy for full qualifications, requirements, and deadlines.
                </p>
            </div>
        @endif

        @if($job->posted_at || $job->until)
            <div class="mx-4 mb-4 flex flex-col items-end gap-1 sm:mx-6 sm:mb-6 lg:absolute lg:bottom-6 lg:right-8 lg:mx-0 lg:mb-0">
                <div class="flex flex-col items-end gap-3 sm:flex-row sm:gap-6">
                    @if($job->posted_at)
                        <div class="text-right">
                            <span class="text-xs font-bold uppercase tracking-wider text-yellow-300">
                                Posted:
                            </span>

                            <span class="font-bold text-white">
                                {{ $job->posted_at->format('F d, Y') }}
                            </span>
                        </div>
                    @endif

                    @if($job->until)
                        <div class="text-right">
                            <span class="text-xs font-bold uppercase tracking-wider text-yellow-300">
                                Until:
                            </span>

                            <span class="font-bold text-white">
                                {{ $job->until->format('F d, Y') }}
                                @if($job->until_time)
                                    {{ \Carbon\Carbon::parse($job->until_time)->format('g:i A') }}
                                @endif
                            </span>
                        </div>
                    @endif
                </div>

                <div class="text-right">
                    <span class="text-xs font-bold uppercase tracking-wider text-yellow-300">
                        No. of Vacancies:
                    </span>

                    <span class="font-bold text-white">
                        {{ $job->slots }}
                    </span>
                </div>
            </div>
        @endif
    </section>

    <div class="lg:flex lg:flex-1">
        @include('applicant.partials.sidebar', ['hideLogout' => true])

        <div class="min-w-0 flex-1">
            @include('applicant.partials.mobile-nav', ['hideLogout' => true])

    <main class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">

        @if(session('error'))
            <div class="mb-8 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
                {{ session('error') }}
            </div>
        @endif

        @if(request('upload_error') === 'too_large')
            <div class="mb-8 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
                Your application was not submitted because the uploaded files were too large in total
                (maximum {{ ini_get('post_max_size') }}B for all documents combined).
                Please compress your PDFs or upload smaller files, then submit again.
            </div>
        @endif

        @if($errors->any())
            <div class="mb-8 rounded-xl border border-red-200 bg-red-50 p-5 text-red-800">
                <p class="font-bold">
                    Please correct the following errors:
                </p>

                <ul class="mt-3 list-inside list-disc space-y-1 text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            // After a failed submit, refill every repeatable section from what the applicant
            // just typed (old input) instead of the saved record, keeping the original indexes
            // so each error lines up with its entry.
            $hasOldInput = ! empty(old());

            $entriesFor = function (string $key, $saved, array $fields) use ($hasOldInput) {
                if ($hasOldInput) {
                    return collect(old($key, []))->map(
                        fn ($entry) => (object) array_merge(array_fill_keys($fields, null), (array) $entry)
                    );
                }

                return collect($saved ?? [])->map(fn ($model) => (object) collect($fields)->mapWithKeys(
                    fn ($field) => [$field => $model->{$field} instanceof \DateTimeInterface
                        ? $model->{$field}->format('Y-m-d')
                        : $model->{$field}]
                )->all());
            };

            $educationEntries = $entriesFor('education', $application?->educations, ['level', 'level_specify', 'school', 'degree', 'year_graduated']);
            $experienceEntries = $entriesFor('experience', $application?->experiences, ['title', 'company', 'first_day', 'last_day', 'details']);
            $trainingEntries = $entriesFor('training', $application?->trainings, ['title', 'hours', 'training_date', 'training_end_date']);
            $eligibilityEntries = $entriesFor('eligibility', $application?->eligibilities, ['license_name', 'license_specify', 'rating', 'date_issued', 'valid_until', 'never_expires']);

            // Next index for entries added with "+ Add", so new ones never reuse an existing index.
            $nextIndex = fn ($entries) => $entries->isEmpty() ? 0 : max(array_map('intval', $entries->keys()->all())) + 1;

            // Server-side errors for one entry, e.g. everything under "education.2.".
            $entryErrors = fn (string $prefix) => collect($errors->getMessages())
                ->filter(fn ($messages, $key) => str_starts_with($key, $prefix))
                ->flatten()
                ->unique();
        @endphp

        <form
            id="application-form"
            method="POST"
            action="{{ $application ? route('applicant.applications.update', $application) : route('apply.submit', $job) }}"
            enctype="multipart/form-data"
            class="space-y-8"
        >
            @csrf
            @if($application)
                @method('PUT')
            @endif

            {{-- Personal information --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="mb-7 border-b border-slate-200 pb-5">
                    <p class="text-xs font-bold uppercase tracking-widest text-government-blue">
                        Section 1
                    </p>

                    <h3 class="mt-2 text-2xl font-black text-government-dark">
                        Personal Information
                    </h3>

                    <p class="mt-2 text-sm text-slate-500">
                        Enter your complete and accurate personal details.
                    </p>
                </div>

                <div class="grid gap-6 md:grid-cols-2">

                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">
                            Full Name <span class="text-red-600">*</span>
                        </label>

                        <input
                            type="text"
                            name="full_name"
                            value="{{ old('full_name', $application?->profile?->full_name) }}"
                            placeholder="Last Name|First Name|Middle Name|Name Extension"
                            required
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">
                            Email Address <span class="text-red-600">*</span>
                        </label>

                        <input
                            type="email"
                            name="email"
                            value="{{ old('email', $application?->profile?->email) }}"
                            required
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">
                            Phone Number
                        </label>

                        <input
                            type="text"
                            name="phone_number"
                            value="{{ old('phone_number', $application?->profile?->phone) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">
                            Complete Address
                        </label>

                        <input
                            type="text"
                            name="address"
                            value="{{ old('address', $application?->profile?->address) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">
                            Birth Date
                        </label>

                        <input
                            type="date"
                            name="birth_date"
                            value="{{ old('birth_date', $application?->profile?->birth_date?->toDateString()) }}"
                            max="{{ now()->toDateString() }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">
                            Sex (at Birth)
                        </label>

                        <select
                            name="sex"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                        >
                            @php $selectedSex = old('sex', $application?->profile?->sex); @endphp
                            <option value="">Select sex</option>
                            <option value="Male" @selected($selectedSex === 'Male')>Male</option>
                            <option value="Female" @selected($selectedSex === 'Female')>Female</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">
                            Civil Status
                        </label>

                        <select
                            name="civil_status"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                        >
                            @php $selectedCivilStatus = old('civil_status', $application?->profile?->civil_status); @endphp
                            <option value="">Select civil status</option>
                            @foreach(['Single', 'Married', 'Widowed', 'Legally Separated', 'Divorced', 'Annulled', 'Other'] as $civilStatus)
                                <option value="{{ $civilStatus }}" @selected($selectedCivilStatus === $civilStatus)>
                                    {{ $civilStatus }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">
                            Religion <span class="text-red-600">*</span>
                        </label>

                        <input
                            type="text"
                            name="religion"
                            value="{{ old('religion', $application?->profile?->religion) }}"
                            required
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">
                            Disability
                        </label>

                        <input
                            type="text"
                            name="disability"
                            value="{{ old('disability', $application?->profile?->disability) }}"
                            placeholder="Optional"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">
                            Ethnic Group
                        </label>

                        <input
                            type="text"
                            name="ethnic_group"
                            value="{{ old('ethnic_group', $application?->profile?->ethnic_group) }}"
                            placeholder="Optional"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                        >
                    </div>
                </div>
            </section>

            {{-- Education --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="mb-7 flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-center">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-government-blue">
                            Section 2
                        </p>

                        <h3 class="mt-2 text-2xl font-black text-government-dark">
                            Educational Background
                        </h3>
                    </div>

                    <button
                        type="button"
                        onclick="addEducation()"
                        class="rounded-xl bg-government-navy px-5 py-3 text-sm font-bold text-white transition hover:bg-government-blue"
                    >
                        + Add Education
                    </button>
                </div>

                <p id="education-empty-warning" class="mb-5 hidden rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700">
                    ⚠ Educational Background is required. Click "+ Add Education" and fill it up before submitting.
                </p>

                @error('education')
                    <p class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700">
                        ⚠ {{ $message }}
                    </p>
                @enderror

                <div id="educationWrapper" class="space-y-5">
                    @foreach($educationEntries as $i => $education)
                        <div class="dynamic-entry rounded-xl border border-slate-200 bg-slate-50 p-5">
                            <div class="mb-4 flex items-center justify-between">
                                <h4 class="font-black text-government-dark">
                                    Education Entry
                                    <span class="text-sm font-semibold text-red-600">(Don't use acronyms)</span>
                                </h4>

                                <button
                                    type="button"
                                    onclick="removeEntry(this)"
                                    class="text-sm font-bold text-red-600 hover:text-red-800"
                                >
                                    Remove
                                </button>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <select
                                    name="education[{{ $i }}][level]"
                                    required
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                                    onchange="toggleEducationSpecify(this)"
                                >
                                    <option value="" disabled hidden>Education level</option>
                                    @foreach(["Bachelor's Degree", "Master's Degree", "Doctorate Degree", "Other's"] as $level)
                                        <option value="{{ $level }}" @selected($education->level === $level)>{{ $level }}</option>
                                    @endforeach
                                </select>

                                <input
                                    type="text"
                                    name="education[{{ $i }}][level_specify]"
                                    data-role="education-specify"
                                    placeholder="Please specify"
                                    value="{{ $education->level_specify }}"
                                    @required($education->level === "Other's")
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100 {{ $education->level === "Other's" ? '' : 'hidden' }}"
                                >

                                <input
                                    type="text"
                                    name="education[{{ $i }}][school]"
                                    required
                                    placeholder="School"
                                    value="{{ $education->school }}"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                                >

                                <input
                                    type="text"
                                    name="education[{{ $i }}][degree]"
                                    required
                                    placeholder="Degree or course"
                                    value="{{ $education->degree }}"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                                >

                                <input
                                    type="text"
                                    name="education[{{ $i }}][year_graduated]"
                                    required
                                    placeholder="Year graduated"
                                    value="{{ $education->year_graduated }}"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                                >

                                <p data-role="required-warning" class="hidden text-sm font-medium text-red-600 md:col-span-2"></p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Work experience --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="mb-7 flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-center">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-government-blue">
                            Section 3
                        </p>

                        <h3 class="mt-2 text-2xl font-black text-government-dark">
                            Work Experience
                        </h3>
                    </div>

                    <button
                        type="button"
                        onclick="addExperience()"
                        class="rounded-xl bg-government-navy px-5 py-3 text-sm font-bold text-white transition hover:bg-government-blue"
                    >
                        + Add Experience
                    </button>
                </div>

                <div id="experienceWrapper" class="space-y-5">
                    @foreach($experienceEntries as $i => $experience)
                        <div class="dynamic-entry rounded-xl border border-slate-200 bg-slate-50 p-5">
                            <div class="mb-4 flex items-center justify-between">
                                <h4 class="font-black text-government-dark">
                                    Experience Entry
                                    <span class="text-sm font-semibold text-red-600">(Don't use acronyms)</span>
                                </h4>

                                <button
                                    type="button"
                                    onclick="removeEntry(this)"
                                    class="text-sm font-bold text-red-600 hover:text-red-800"
                                >
                                    Remove
                                </button>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <input
                                    type="text"
                                    name="experience[{{ $i }}][title]"
                                    placeholder="Position title"
                                    value="{{ $experience->title }}"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                                >

                                <input
                                    type="text"
                                    name="experience[{{ $i }}][company]"
                                    placeholder="Company or agency"
                                    value="{{ $experience->company }}"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                                >

                                <input
                                    type="text"
                                    name="experience[{{ $i }}][first_day]"
                                    placeholder="First Day of Service (Month and Year)"
                                    value="{{ $experience->first_day }}"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                                >

                                <input
                                    type="text"
                                    name="experience[{{ $i }}][last_day]"
                                    placeholder="Last Day of Service (Month and Year)"
                                    value="{{ $experience->last_day }}"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                                >

                                <textarea
                                    name="experience[{{ $i }}][details]"
                                    placeholder="Responsibilities or details"
                                    rows="3"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                                >{{ $experience->details }}</textarea>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Training --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="mb-7 flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-center">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-government-blue">
                            Section 4
                        </p>

                        <h3 class="mt-2 text-2xl font-black text-government-dark">
                            Trainings and Seminars
                        </h3>
                    </div>

                    <button
                        type="button"
                        onclick="addTraining()"
                        class="rounded-xl bg-government-navy px-5 py-3 text-sm font-bold text-white transition hover:bg-government-blue"
                    >
                        + Add Training
                    </button>
                </div>

                <div id="trainingWrapper" class="space-y-5">
                    @foreach($trainingEntries as $i => $training)
                        <div class="dynamic-entry rounded-xl border border-slate-200 bg-slate-50 p-5">
                            <div class="mb-4 flex items-center justify-between">
                                <h4 class="font-black text-government-dark">
                                    Training Entry
                                    <span class="text-sm font-semibold text-red-600">(Don't use acronyms)</span>
                                </h4>

                                <button
                                    type="button"
                                    onclick="removeEntry(this)"
                                    class="text-sm font-bold text-red-600 hover:text-red-800"
                                >
                                    Remove
                                </button>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <input
                                    type="text"
                                    name="training[{{ $i }}][title]"
                                    placeholder="Training or seminar title"
                                    value="{{ $training->title }}"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                                >

                                <input
                                    type="number"
                                    min="0"
                                    step="1"
                                    name="training[{{ $i }}][hours]"
                                    placeholder="Number of hours"
                                    value="{{ $training->hours }}"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                                >

                                <div class="md:col-span-2">
                                    <label
                                        for="training_start_{{ $i }}"
                                        class="mb-2 block text-sm font-bold text-government-dark"
                                    >
                                        Start of Training
                                    </label>

                                    <input
                                        id="training_start_{{ $i }}"
                                        type="date"
                                        name="training[{{ $i }}][training_date]"
                                        value="{{ $training->training_date }}"
                                        max="{{ now()->format('Y-m-d') }}"
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                                    >

                                    <label
                                        for="training_end_{{ $i }}"
                                        class="mb-2 block text-sm font-bold text-government-dark"
                                    >
                                        End of Training
                                    </label>

                                    <input
                                        id="training_end_{{ $i }}"
                                        type="date"
                                        name="training[{{ $i }}][training_end_date]"
                                        value="{{ $training->training_end_date }}"
                                        min="{{ $training->training_date }}"
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                                    >

                                    <p data-role="training-date-warning" class="mt-2 hidden text-sm font-medium text-red-600">
                                        ⚠ End of Training must be on or after the Start of Training.
                                    </p>

                                    <p class="mt-2 text-xs text-slate-500">
                                        Select the exact date (day, month and year) when the training or seminar started and ended.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Eligibility --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="mb-7 flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-center">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-government-blue">
                            Section 5
                        </p>

                        <h3 class="mt-2 text-2xl font-black text-government-dark">
                            Eligibility and Licenses
                        </h3>
                    </div>

                    <button
                        type="button"
                        onclick="addEligibility()"
                        class="rounded-xl bg-government-navy px-5 py-3 text-sm font-bold text-white transition hover:bg-government-blue"
                    >
                        + Add Eligibility
                    </button>
                </div>

                <p id="eligibility-empty-warning" class="mb-5 hidden rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700">
                    ⚠ Eligibility and Licenses is required. Click "+ Add Eligibility" and fill it up before submitting.
                </p>

                @error('eligibility')
                    <p class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700">
                        ⚠ {{ $message }}
                    </p>
                @enderror

                <div id="eligibilityWrapper" class="space-y-5">
                    @foreach($eligibilityEntries as $i => $eligibility)
                        <div class="dynamic-entry rounded-xl border border-slate-200 bg-slate-50 p-5">
                            <div class="mb-4 flex items-center justify-between">
                                <h4 class="font-black text-government-dark">
                                    Eligibility Entry
                                    <span class="text-sm font-semibold text-red-600">(Don't use acronyms)</span>
                                </h4>

                                <button
                                    type="button"
                                    onclick="removeEntry(this)"
                                    class="text-sm font-bold text-red-600 hover:text-red-800"
                                >
                                    Remove
                                </button>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <select
                                    name="eligibility[{{ $i }}][license_name]"
                                    required
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                                    onchange="toggleEligibilitySpecify(this)"
                                >
                                    <option value="" disabled hidden>Eligibility or license name</option>
                                    @foreach(['CS Sub-Professional', 'CSC Professional', 'RA1080', "Other's"] as $licenseName)
                                        <option value="{{ $licenseName }}" @selected($eligibility->license_name === $licenseName)>{{ $licenseName }}</option>
                                    @endforeach
                                </select>

                                <input
                                    type="text"
                                    name="eligibility[{{ $i }}][license_specify]"
                                    data-role="eligibility-specify"
                                    placeholder="Please specify"
                                    value="{{ $eligibility->license_specify }}"
                                    @required(in_array($eligibility->license_name, ['RA1080', "Other's"], true))
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100 {{ in_array($eligibility->license_name, ['RA1080', "Other's"], true) ? '' : 'hidden' }}"
                                >

                                <input
                                    type="text"
                                    name="eligibility[{{ $i }}][rating]"
                                    required
                                    placeholder="Rating"
                                    value="{{ $eligibility->rating }}"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                                >

                                <div>
                                    <label class="mb-2 block text-sm font-bold text-government-dark">
                                        Date Issued
                                    </label>

                                    <input
                                        type="date"
                                        name="eligibility[{{ $i }}][date_issued]"
                                        required
                                        value="{{ $eligibility->date_issued }}"
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                                    >
                                </div>

                                <div data-role="valid-until-wrapper" class="{{ $eligibility->never_expires ? 'hidden' : '' }}">
                                    <label class="mb-2 block text-sm font-bold text-government-dark">
                                        Valid Until
                                    </label>

                                    <input
                                        type="date"
                                        name="eligibility[{{ $i }}][valid_until]"
                                        @required(! $eligibility->never_expires)
                                        value="{{ $eligibility->valid_until }}"
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                                    >

                                    <p data-role="eligibility-expired-warning" class="mt-2 hidden text-sm font-medium text-red-600">
                                        ⚠ This license/eligibility has already expired. The application cannot be submitted.
                                    </p>
                                </div>

                                <label class="mt-1 ml-auto flex w-fit items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 md:col-span-2">
                                    <input
                                        type="checkbox"
                                        name="eligibility[{{ $i }}][never_expires]"
                                        value="1"
                                        @checked($eligibility->never_expires)
                                        class="h-4 w-4 rounded border-slate-300 text-government-navy"
                                        onchange="toggleNeverExpires(this)"
                                    >

                                    <span class="text-xs font-semibold text-slate-700">
                                        Never expires
                                    </span>
                                </label>

                                <p data-role="required-warning" class="hidden text-sm font-medium text-red-600 md:col-span-2"></p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Documents --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="mb-7 border-b border-slate-200 pb-5">
                    <p class="text-xs font-bold uppercase tracking-widest text-government-blue">
                        Section 6
                    </p>

                    <h3 class="mt-2 text-2xl font-black text-government-dark">
                        Supporting Documents
                    </h3>

                    <p class="mt-2 text-sm text-slate-500">
                        PDF files only. Maximum file size is 10 MB per document.
                    </p>

                    @if($hasOldInput)
                        <p class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm font-medium text-amber-800">
                            ⚠ Your application was not saved yet. For security, browsers do not keep selected files,
                            so please attach the PDF documents you selected again before submitting.
                        </p>
                    @endif
                </div>

                @php
                    $existingDocuments = $application?->documents->keyBy('type') ?? collect();
                @endphp

                <div class="grid gap-5 md:grid-cols-2">
                    @foreach([
                        'letter_of_intent' => 'Letter of Intent',
                        'tor_diploma' => 'TOR / Diploma',
                        'prc_license' => 'PRC License',
                        'eligibility_file' => 'Eligibility Document',
                        'training_certificates' => 'Training Certificates',
                        'employment_records' => 'Employment Records',
                        'latest_appointment' => 'Latest Appointment',
                        'performance_rating' => 'Performance Rating',
                        'cav' => 'CAV',
                        'movs' => 'Other MOVs/Documents',
                    ] as $field => $label)
                        @php
                            $existingDocument = $existingDocuments->get($field);
                        @endphp

                        <div class="rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 p-5">
                            <label
                                for="{{ $field }}"
                                class="mb-3 block font-bold text-government-dark"
                            >
                                {{ $label }}
                            </label>

                            @php
                                // Older uploads have no saved filename, so fall back to the document's label.
                                $existingName = $existingDocument
                                    ? ($existingDocument->original_name ?: $label.'.pdf')
                                    : null;
                            @endphp

                            {{-- A browser can't pre-fill a file input, so the real input is hidden and
                                 this picker shows the file already on record instead of "No file chosen". --}}
                            <div data-role="file-picker" class="flex flex-wrap items-center gap-3">
                                <input
                                    type="file"
                                    id="{{ $field }}"
                                    name="{{ $field }}"
                                    accept=".pdf,application/pdf"
                                    class="sr-only"
                                >

                                <label
                                    for="{{ $field }}"
                                    class="cursor-pointer rounded-lg bg-government-navy px-4 py-2.5 text-sm font-bold text-white transition hover:bg-government-blue"
                                >
                                    {{ $existingDocument ? 'Replace File' : 'Choose File' }}
                                </label>

                                <span data-role="file-status" class="min-w-0 break-all text-sm">
                                    @if($existingDocument)
                                        <span class="font-semibold text-green-700">✓ {{ $existingName }}</span>
                                        <a
                                            href="{{ route('public-file', $existingDocument->file_path) }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="ml-1 font-bold text-government-blue hover:underline"
                                        >
                                            View
                                        </a>
                                    @else
                                        <span class="text-slate-500">No file chosen</span>
                                    @endif
                                </span>
                            </div>

                            @if($existingDocument)
                                <p data-role="file-hint" class="mt-2 text-xs text-slate-500">
                                    Already uploaded. Leave it as is to keep this file, or click "Replace File" to upload a new one.
                                </p>
                            @endif

                            @error($field)
                                <p class="mt-2 text-sm font-medium text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-2xl border border-blue-100 bg-government-light p-6">
                <label class="flex items-start gap-3">
                    <input
                        type="checkbox"
                        required
                        class="mt-1 h-5 w-5 rounded border-slate-300 text-government-navy focus:ring-government-blue"
                    >

                    <span class="text-sm leading-6 text-slate-700">
                        I certify that the information provided in this
                        application is true and complete. I understand that false
                        information may result in disqualification.
                    </span>
                </label>
            </section>

            <div
                id="upload-size-error"
                class="hidden rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"
            ></div>

            <div class="flex flex-col-reverse justify-end gap-3 sm:flex-row">
                <a
                    href="{{ $application ? route('applicant.dashboard') : route('jobs.index') }}"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-6 py-3.5 font-bold text-slate-700 transition hover:bg-slate-100"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-government-navy px-8 py-3.5 font-bold text-white shadow-lg transition hover:bg-government-blue focus:ring-4 focus:ring-blue-200"
                >
                    {{ $application ? 'Save Changes' : 'Submit Application' }}
                </button>
            </div>
        </form>
    </main>

    @php
        // PHP's post_max_size (e.g. "40M") in bytes; the whole form must fit under it.
        $postMaxSize = ini_get('post_max_size');
        $postMaxBytes = (int) $postMaxSize * match (strtoupper(substr(trim($postMaxSize), -1))) {
            'G' => 1024 ** 3,
            'M' => 1024 ** 2,
            'K' => 1024,
            default => 1,
        };
    @endphp

    <script>
        // End of Training must be on or after Start of Training.
        (function () {
            const form = document.getElementById('application-form');
            const message = 'End of Training must be on or after the Start of Training.';

            function checkTrainingDates(entry) {
                const start = entry.querySelector('input[name$="[training_date]"]');
                const end = entry.querySelector('input[name$="[training_end_date]"]');
                const warning = entry.querySelector('[data-role="training-date-warning"]');

                if (! start || ! end) {
                    return;
                }

                end.min = start.value;

                const invalid = start.value !== '' && end.value !== '' && end.value < start.value;

                // setCustomValidity makes the browser refuse to submit the form.
                end.setCustomValidity(invalid ? message : '');
                end.classList.toggle('border-red-500', invalid);
                warning?.classList.toggle('hidden', ! invalid);
            }

            ['input', 'change'].forEach(function (type) {
                form.addEventListener(type, function (event) {
                    if (/\[training_(end_)?date\]$/.test(event.target.name || '')) {
                        checkTrainingDates(event.target.closest('.dynamic-entry'));
                    }
                });
            });

            form.querySelectorAll('.dynamic-entry').forEach(checkTrainingDates);
        })();

        // Block submitting when an eligibility's Valid Until date has passed.
        (function () {
            const form = document.getElementById('application-form');
            const now = new Date();
            const today = now.getFullYear() + '-'
                + String(now.getMonth() + 1).padStart(2, '0') + '-'
                + String(now.getDate()).padStart(2, '0');

            function checkEligibilityExpiry(entry) {
                const validUntil = entry.querySelector('input[name$="[valid_until]"]');
                const neverExpires = entry.querySelector('input[name$="[never_expires]"]');
                const warning = entry.querySelector('[data-role="eligibility-expired-warning"]');

                if (! validUntil || ! warning) {
                    return;
                }

                const expired = ! neverExpires?.checked && validUntil.value !== '' && validUntil.value < today;

                // setCustomValidity makes the browser refuse to submit the form.
                validUntil.setCustomValidity(expired ? 'This license/eligibility has already expired. The application cannot be submitted.' : '');
                validUntil.classList.toggle('border-red-500', expired);
                warning.classList.toggle('hidden', ! expired);
            }

            ['input', 'change'].forEach(function (type) {
                form.addEventListener(type, function (event) {
                    if (/\[(valid_until|never_expires)\]$/.test(event.target.name || '')) {
                        checkEligibilityExpiry(event.target.closest('.dynamic-entry'));
                    }
                });
            });

            form.querySelectorAll('.dynamic-entry').forEach(checkEligibilityExpiry);
        })();

        // Required sections: at least one entry, and every required field of every entry filled.
        function requireSection(wrapperId, emptyWarningId, labels) {
            const form = document.getElementById('application-form');
            const wrapper = document.getElementById(wrapperId);
            const emptyWarning = document.getElementById(emptyWarningId);
            const entrySelector = '#' + wrapperId + ' .dynamic-entry';

            function checkEntry(entry) {
                const warning = entry.querySelector('[data-role="required-warning"]');
                const missing = [];

                entry.querySelectorAll('input:not([type="checkbox"]), select').forEach(function (field) {
                    const empty = field.required && field.value.trim() === '';
                    const key = field.name.match(/\[(\w+)\]$/)[1];

                    // Keep the red border from other checks (e.g. expired license).
                    field.classList.toggle('border-red-500', empty || field.validity.customError);

                    if (empty) {
                        missing.push(labels[key] || key);
                    }
                });

                warning.textContent = missing.length
                    ? '⚠ Please fill up: ' + missing.join(', ') + '.'
                    : '';
                warning.classList.toggle('hidden', missing.length === 0);
                entry.dataset.checked = '1';
            }

            // Fires for each blank required field when the applicant clicks Submit.
            form.addEventListener('invalid', function (event) {
                const entry = event.target.closest(entrySelector);

                if (entry) {
                    checkEntry(entry);
                }
            }, true);

            // Once a warning is shown, update it live as the applicant types.
            ['input', 'change'].forEach(function (type) {
                form.addEventListener(type, function (event) {
                    const entry = event.target.closest(entrySelector);

                    if (entry && entry.dataset.checked) {
                        checkEntry(entry);
                    }
                });
            });

            // At least one entry is required.
            form.addEventListener('submit', function (event) {
                const hasEntry = wrapper.querySelector('.dynamic-entry') !== null;

                emptyWarning.classList.toggle('hidden', hasEntry);

                if (! hasEntry) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    emptyWarning.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });

            new MutationObserver(function () {
                if (wrapper.querySelector('.dynamic-entry')) {
                    emptyWarning.classList.add('hidden');
                }
            }).observe(wrapper, { childList: true });
        }

        // After a failed submit: mark every field the server rejected and show its message
        // right below it (or under its entry), then scroll to the first problem.
        (function () {
            const serverErrors = @json($errors->getMessages());
            const form = document.getElementById('application-form');
            let firstProblem = null;

            Object.entries(serverErrors).forEach(function ([key, messages]) {
                // "education.2.school" -> "education[2][school]"
                const parts = key.split('.');
                const name = parts[0] + parts.slice(1).map((part) => '[' + part + ']').join('');
                const field = form.querySelector('[name="' + name + '"]');

                if (! field) {
                    return;
                }

                // Document uploads already print their own error; the file input itself is
                // hidden, so outline its box instead.
                if (field.type === 'file') {
                    const box = field.closest('.border-dashed') || field;
                    box.classList.add('border-red-400');
                    firstProblem = firstProblem || box;
                    return;
                }

                field.classList.add('border-red-500');
                firstProblem = firstProblem || field;

                const entry = field.closest('.dynamic-entry');
                let warning;

                if (entry) {
                    // One warning line per entry, listing everything wrong with it.
                    warning = entry.querySelector('[data-role="server-warning"]');

                    if (! warning) {
                        warning = document.createElement('p');
                        warning.dataset.role = 'server-warning';
                        warning.className = 'mt-3 text-sm font-medium text-red-600';
                        entry.appendChild(warning);
                    }
                } else {
                    warning = document.createElement('p');
                    warning.className = 'mt-2 text-sm font-medium text-red-600';
                    field.insertAdjacentElement('afterend', warning);
                }

                messages.forEach(function (message) {
                    const line = document.createElement('span');
                    line.className = 'block';
                    line.textContent = '⚠ ' + message;
                    warning.appendChild(line);
                });
            });

            // Clear a field's red border once the applicant edits it.
            form.addEventListener('input', function (event) {
                if (! event.target.validity || event.target.validity.valid) {
                    event.target.classList.remove('border-red-500');
                }
            });

            if (firstProblem) {
                firstProblem.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        })();

        // Supporting documents: show the chosen file's name, or go back to the file on record.
        document.querySelectorAll('[data-role="file-picker"]').forEach(function (picker) {
            const input = picker.querySelector('input[type="file"]');
            const status = picker.querySelector('[data-role="file-status"]');
            const hint = picker.parentElement.querySelector('[data-role="file-hint"]');
            const originalStatus = status.innerHTML;
            const originalHint = hint ? hint.textContent : '';
            const hasExisting = hint !== null;

            input.addEventListener('change', function () {
                const file = input.files[0];

                if (! file) {
                    status.innerHTML = originalStatus;
                    if (hint) hint.textContent = originalHint;
                    return;
                }

                status.innerHTML = '';
                const name = document.createElement('span');
                name.className = 'font-semibold text-government-dark';
                name.textContent = '📄 ' + file.name + ' (' + (file.size / 1024 / 1024).toFixed(1) + ' MB)';
                status.appendChild(name);

                if (hint) {
                    hint.textContent = hasExisting
                        ? 'This new file will replace the one already uploaded when you save.'
                        : '';
                }

                picker.closest('.border-dashed')?.classList.remove('border-red-400');
            });
        });

        requireSection('educationWrapper', 'education-empty-warning', {
            level: 'Education level',
            level_specify: 'Please specify',
            school: 'School',
            degree: 'Degree or course',
            year_graduated: 'Year graduated',
        });

        requireSection('eligibilityWrapper', 'eligibility-empty-warning', {
            license_name: 'Eligibility or license name',
            license_specify: 'Please specify',
            rating: 'Rating',
            date_issued: 'Date Issued',
            valid_until: 'Valid Until',
        });
    </script>

    <script>
        (function () {
            const form = document.getElementById('application-form');
            const errorBox = document.getElementById('upload-size-error');
            const perFileLimit = 10 * 1024 * 1024;
            // Leave 1 MB headroom for the text fields sent with the files.
            const totalLimit = {{ $postMaxBytes }} > 0 ? {{ $postMaxBytes }} - 1024 * 1024 : Infinity;
            const toMb = (bytes) => (bytes / 1024 / 1024).toFixed(1) + ' MB';

            form.addEventListener('submit', function (event) {
                const problems = [];
                let total = 0;

                form.querySelectorAll('input[type="file"]').forEach(function (input) {
                    Array.from(input.files).forEach(function (file) {
                        total += file.size;

                        if (file.size > perFileLimit) {
                            const label = form.querySelector('label[for="' + input.id + '"]');
                            problems.push((label ? label.textContent.trim() : file.name) + ' is ' + toMb(file.size) + ' (max 10 MB per document).');
                        }
                    });
                });

                if (total > totalLimit) {
                    problems.push('All documents together are ' + toMb(total) + ', but the maximum for one submission is ' + toMb(totalLimit) + '. Please compress your PDFs or upload smaller files.');
                }

                if (problems.length) {
                    event.preventDefault();
                    errorBox.innerHTML = '<p class="font-bold">Your files are too large:</p><ul class="mt-2 list-inside list-disc space-y-1"></ul>';
                    problems.forEach(function (text) {
                        const li = document.createElement('li');
                        li.textContent = text;
                        errorBox.querySelector('ul').appendChild(li);
                    });
                    errorBox.classList.remove('hidden');
                    errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    errorBox.classList.add('hidden');
                }
            });
        })();
    </script>

    <script>
        let educationIndex = {{ $nextIndex($educationEntries) }};
        let experienceIndex = {{ $nextIndex($experienceEntries) }};
        let trainingIndex = {{ $nextIndex($trainingEntries) }};
        let eligibilityIndex = {{ $nextIndex($eligibilityEntries) }};

        const inputClass =
            'w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100';

        function removeEntry(button) {
            button.closest('.dynamic-entry').remove();
        }

        function toggleEducationSpecify(selectEl) {
            const entry = selectEl.closest('.dynamic-entry');
            const specifyInput = entry.querySelector('[data-role="education-specify"]');

            if (selectEl.value === "Other's") {
                specifyInput.classList.remove('hidden');
                specifyInput.required = true;
            } else {
                specifyInput.classList.add('hidden');
                specifyInput.required = false;
                specifyInput.value = '';
            }
        }

        function addEducation() {
            const wrapper = document.getElementById('educationWrapper');

            wrapper.insertAdjacentHTML('beforeend', `
                <div class="dynamic-entry rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <h4 class="font-black text-government-dark">
                            Education Entry
                            <span class="text-sm font-semibold text-red-600">(Don't use acronyms)</span>
                        </h4>

                        <button
                            type="button"
                            onclick="removeEntry(this)"
                            class="text-sm font-bold text-red-600 hover:text-red-800"
                        >
                            Remove
                        </button>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        
                        <select
                            name="education[${educationIndex}][level]"
                            required
                            class="${inputClass}"
                            onchange="toggleEducationSpecify(this)"
                        >
                            <option value="" disabled selected hidden>Education level</option>
                            <option value="Bachelor's Degree">Bachelor's Degree</option>
                            <option value="Master's Degree">Master's Degree</option>
                            <option value="Doctorate Degree">Doctorate Degree</option>
                            <option value="Other's">Other's</option>
                        </select>

                        <input
                            type="text"
                            name="education[${educationIndex}][level_specify]"
                            data-role="education-specify"
                            placeholder="Please specify"
                            class="${inputClass} hidden"
                        >

                        <input
                            type="text"
                            name="education[${educationIndex}][school]"
                            required
                            placeholder="School"
                            class="${inputClass}"
                        >

                        <input
                            type="text"
                            name="education[${educationIndex}][degree]"
                            required
                            placeholder="Degree or course"
                            class="${inputClass}"
                        >

                        <input
                            type="text"
                            name="education[${educationIndex}][year_graduated]"
                            required
                            placeholder="Year graduated"
                            class="${inputClass}"
                        >

                        <p data-role="required-warning" class="hidden text-sm font-medium text-red-600 md:col-span-2"></p>
                    </div>
                </div>
            `);

            educationIndex++;
        }

        function addExperience() {
            const wrapper = document.getElementById('experienceWrapper');

            wrapper.insertAdjacentHTML('beforeend', `
                <div class="dynamic-entry rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <h4 class="font-black text-government-dark">
                            Experience Entry
                            <span class="text-sm font-semibold text-red-600">(Don't use acronyms)</span>
                        </h4>

                        <button
                            type="button"
                            onclick="removeEntry(this)"
                            class="text-sm font-bold text-red-600 hover:text-red-800"
                        >
                            Remove
                        </button>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <input
                            type="text"
                            name="experience[${experienceIndex}][title]"
                            placeholder="Position title"
                            class="${inputClass}"
                        >

                        <input
                            type="text"
                            name="experience[${experienceIndex}][company]"
                            placeholder="Company or agency"
                            class="${inputClass}"
                        >

                        <input
                            type="text"
                            name="experience[${experienceIndex}][first_day]"
                            placeholder="First Day of Service (Month and Year)"
                            class="${inputClass}"
                        >
                        
                        <input
                            type="text"
                            name="experience[${experienceIndex}][last_day]"
                            placeholder="Last Day of Service (Month and Year)"
                            class="${inputClass}"
                        >

                        <textarea
                            name="experience[${experienceIndex}][details]"
                            placeholder="Responsibilities or details"
                            rows="3"
                            class="${inputClass}"
                        ></textarea>
                    </div>
                </div>
            `);

            experienceIndex++;
        }

        function addTraining() {
            const wrapper = document.getElementById('trainingWrapper');

            wrapper.insertAdjacentHTML('beforeend', `
                <div class="dynamic-entry rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <h4 class="font-black text-government-dark">
                            Training Entry
                            <span class="text-sm font-semibold text-red-600">(Don't use acronyms)</span>
                        </h4>

                        <button
                            type="button"
                            onclick="removeEntry(this)"
                            class="text-sm font-bold text-red-600 hover:text-red-800"
                        >
                            Remove
                        </button>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <input
                            type="text"
                            name="training[${trainingIndex}][title]"
                            placeholder="Training or seminar title"
                            class="${inputClass}"
                        >

                        <input
                            type="number"
                            min="0"
                            step="1"
                            name="training[${trainingIndex}][hours]"
                            placeholder="Number of hours"
                            class="${inputClass}"
                        >

                        <div class="md:col-span-2">
                            <label
                                for="training_start_${trainingIndex}"
                                class="mb-2 block text-sm font-bold text-government-dark"
                            >
                                Start of Training
                            </label>

                            <input
                                id="training_start_${trainingIndex}"
                                type="date"
                                name="training[${trainingIndex}][training_date]"
                                max="{{ now()->format('Y-m-d') }}"
                                class="${inputClass}"
                            >

                            <label
                                for="training_end_${trainingIndex}"
                                class="mb-2 block text-sm font-bold text-government-dark"
                            >
                                End of Training
                            </label>

                            <input
                                id="training_end_${trainingIndex}"
                                type="date"
                                name="training[${trainingIndex}][training_end_date]"
                                class="${inputClass}"
                            >

                            <p data-role="training-date-warning" class="mt-2 hidden text-sm font-medium text-red-600">
                                ⚠ End of Training must be on or after the Start of Training.
                            </p>

                            <p class="mt-2 text-xs text-slate-500">
                                Select the exact date (day, month and year) when the training or seminar started and ended.
                            </p>
                        </div>
                    </div>
                </div>
            `);

            trainingIndex++;
        }

        function toggleEligibilitySpecify(selectEl) {
            const entry = selectEl.closest('.dynamic-entry');
            const specifyInput = entry.querySelector('[data-role="eligibility-specify"]');

            if (selectEl.value === 'RA1080' || selectEl.value === "Other's") {
                specifyInput.classList.remove('hidden');
                specifyInput.required = true;
            } else {
                specifyInput.classList.add('hidden');
                specifyInput.required = false;
                specifyInput.value = '';
            }
        }

        function toggleNeverExpires(checkboxEl) {
            const entry = checkboxEl.closest('.dynamic-entry');
            const validUntilWrapper = entry.querySelector('[data-role="valid-until-wrapper"]');
            const validUntilInput = validUntilWrapper.querySelector('input');

            if (checkboxEl.checked) {
                validUntilWrapper.classList.add('hidden');
                validUntilInput.required = false;
                validUntilInput.value = '';
            } else {
                validUntilWrapper.classList.remove('hidden');
                validUntilInput.required = true;
            }
        }

        function addEligibility() {
            const wrapper = document.getElementById('eligibilityWrapper');

            wrapper.insertAdjacentHTML('beforeend', `
                <div class="dynamic-entry rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <h4 class="font-black text-government-dark">
                            Eligibility Entry
                            <span class="text-sm font-semibold text-red-600">(Don't use acronyms)</span>
                        </h4>

                        <button
                            type="button"
                            onclick="removeEntry(this)"
                            class="text-sm font-bold text-red-600 hover:text-red-800"
                        >
                            Remove
                        </button>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <select
                            name="eligibility[${eligibilityIndex}][license_name]"
                            required
                            class="${inputClass}"
                            onchange="toggleEligibilitySpecify(this)"
                        >
                            <option value="" disabled selected hidden>Eligibility or license name</option>
                            <option value="CS Sub-Professional">CS Sub-Professional</option>
                            <option value="CSC Professional">CSC Professional</option>
                            <option value="RA1080">RA1080</option>
                            <option value="Other's">Other's</option>
                        </select>

                        <input
                            type="text"
                            name="eligibility[${eligibilityIndex}][license_specify]"
                            data-role="eligibility-specify"
                            placeholder="Please specify"
                            class="${inputClass} hidden"
                        >

                        <input
                            type="text"
                            name="eligibility[${eligibilityIndex}][rating]"
                            required
                            placeholder="Rating"
                            class="${inputClass}"
                        >

                        <div>
                            <label class="mb-2 block text-sm font-bold text-government-dark">
                                Date Issued
                            </label>

                            <input
                                type="date"
                                name="eligibility[${eligibilityIndex}][date_issued]"
                                required
                                class="${inputClass}"
                            >
                        </div>

                        <div data-role="valid-until-wrapper">
                            <label class="mb-2 block text-sm font-bold text-government-dark">
                                Valid Until
                            </label>

                            <input
                                type="date"
                                name="eligibility[${eligibilityIndex}][valid_until]"
                                required
                                class="${inputClass}"
                            >

                            <p data-role="eligibility-expired-warning" class="mt-2 hidden text-sm font-medium text-red-600">
                                ⚠ This license/eligibility has already expired. The application cannot be submitted.
                            </p>
                        </div>

                        <label class="mt-1 ml-auto flex w-fit items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 md:col-span-2">
                            <input
                                type="checkbox"
                                name="eligibility[${eligibilityIndex}][never_expires]"
                                value="1"
                                class="h-4 w-4 rounded border-slate-300 text-government-navy"
                                onchange="toggleNeverExpires(this)"
                            >

                            <span class="text-xs font-semibold text-slate-700">
                                Never expires
                            </span>
                        </label>

                        <p data-role="required-warning" class="hidden text-sm font-medium text-red-600 md:col-span-2"></p>
                    </div>
                </div>
            `);

            eligibilityIndex++;
        }

        document.addEventListener('DOMContentLoaded', function () {
            if (educationIndex === 0) {
                addEducation();
            }

            if (experienceIndex === 0) {
                addExperience();
            }

            if (trainingIndex === 0) {
                addTraining();
            }

            if (eligibilityIndex === 0) {
                addEligibility();
            }
        });
    </script>

        </div>
    </div>

</body>
</html>
