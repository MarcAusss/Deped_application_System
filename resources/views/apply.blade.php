<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Apply for {{ $job->title }}</title>

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
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-5 px-4 py-4 sm:px-6 lg:px-8">

            <a
                href="{{ route('jobs.index') }}"
                class="flex items-center gap-4"
            >
                <img src="{{ url ('images/Department_of_Education_(DepEd).svg.webp') }}" alt="" class="w-20">

                <div>
                    <p class="text-lg font-black uppercase tracking-widest text-government-dark sm:text-xl">
                        SCHOOL DIVISION OFFICE OF ALBAY - REGION V
                    </p>

                    <h1 class="text-sm font-bold text-government-gold">
                        Lignon Hill, Bogtong, Legazpi City, Albay
                    </h1>
                </div>
            </a>

            <a
                href="{{ route('jobs.index') }}"
                class="rounded-lg border border-government-navy px-4 py-2 text-sm font-bold text-government-navy transition hover:bg-government-navy hover:text-white"
            >
                Back to Jobs
            </a>
        </div>
    </header>

    <section class="relative overflow-hidden bg-gradient-to-r from-government-dark to-government-blue text-white">
        <div class="mx-auto flex max-w-5xl flex-col items-center justify-center gap-2 px-4 py-12 sm:flex-row sm:px-6 lg:px-8">
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

        @if($job->attachment_path || $job->csc_publication_path)
            <div class="mx-4 mb-4 flex flex-col items-start gap-2 sm:mx-6 sm:mb-6 lg:absolute lg:bottom-6 lg:left-8 lg:mx-0 lg:mb-0">
                <div class="flex flex-col items-start gap-3 sm:flex-row sm:flex-wrap sm:gap-6">
                    @if($job->attachment_path)
                        <a
                            href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($job->attachment_path) }}"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-white/30 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-white/10"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9.75v6.75m0 0-3-3m3 3 3-3m-8.25 6a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                            </svg>
                             D.M Notice
                        </a>
                    @endif

                    @if($job->csc_publication_path)
                        <a
                            href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($job->csc_publication_path) }}"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-white/30 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-white/10"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9.75v6.75m0 0-3-3m3 3 3-3m-8.25 6a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                            </svg>
                             CSC Publication of Vacancy
                        </a>
                    @endif
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

    <main class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">

        @if(session('error'))
            <div class="mb-8 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
                {{ session('error') }}
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

        <form
            method="POST"
            action="{{ route('apply.submit', $job) }}"
            enctype="multipart/form-data"
            class="space-y-8"
        >
            @csrf

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
                            value="{{ old('full_name') }}"
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
                            value="{{ old('email') }}"
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
                            value="{{ old('phone_number') }}"
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
                            value="{{ old('address') }}"
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
                            value="{{ old('birth_date') }}"
                            max="{{ now()->toDateString() }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">
                            Sex
                        </label>

                        <select
                            name="sex"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-government-blue focus:ring-4 focus:ring-blue-100"
                        >
                            <option value="">Select sex</option>
                            <option value="Male" @selected(old('sex') === 'Male')>Male</option>
                            <option value="Female" @selected(old('sex') === 'Female')>Female</option>
                            <option value="Prefer not to say" @selected(old('sex') === 'Prefer not to say')>Prefer not to say</option>
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
                            <option value="">Select civil status</option>
                            @foreach(['Single', 'Married', 'Widowed', 'Separated', 'Other'] as $civilStatus)
                                <option value="{{ $civilStatus }}" @selected(old('civil_status') === $civilStatus)>
                                    {{ $civilStatus }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">
                            Religion
                        </label>

                        <input
                            type="text"
                            name="religion"
                            value="{{ old('religion') }}"
                            placeholder="Optional"
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
                            value="{{ old('disability') }}"
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
                            value="{{ old('ethnic_group') }}"
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

                <div id="educationWrapper" class="space-y-5"></div>
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

                <div id="experienceWrapper" class="space-y-5"></div>
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

                <div id="trainingWrapper" class="space-y-5"></div>
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

                <div id="eligibilityWrapper" class="space-y-5"></div>
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
                </div>

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
                        'movs' => 'MOVs',
                    ] as $field => $label)
                        <div class="rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 p-5">
                            <label
                                for="{{ $field }}"
                                class="mb-3 block font-bold text-government-dark"
                            >
                                {{ $label }}
                            </label>

                            <input
                                type="file"
                                id="{{ $field }}"
                                name="{{ $field }}"
                                accept=".pdf,application/pdf"
                                class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-government-navy file:px-4 file:py-2.5 file:font-bold file:text-white hover:file:bg-government-blue"
                            >

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

            <div class="flex flex-col-reverse justify-end gap-3 sm:flex-row">
                <a
                    href="{{ route('jobs.index') }}"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-6 py-3.5 font-bold text-slate-700 transition hover:bg-slate-100"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-government-navy px-8 py-3.5 font-bold text-white shadow-lg transition hover:bg-government-blue focus:ring-4 focus:ring-blue-200"
                >
                    Submit Application
                </button>
            </div>
        </form>
    </main>

    <script>
        let educationIndex = 0;
        let experienceIndex = 0;
        let trainingIndex = 0;
        let eligibilityIndex = 0;

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
            } else {
                specifyInput.classList.add('hidden');
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
                            placeholder="School"
                            class="${inputClass}"
                        >

                        <input
                            type="text"
                            name="education[${educationIndex}][degree]"
                            placeholder="Degree or course"
                            class="${inputClass}"
                        >

                        <input
                            type="text"
                            name="education[${educationIndex}][year_graduated]"
                            placeholder="Year graduated"
                            class="${inputClass}"
                        >
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
                                type="month"
                                name="training[${trainingIndex}][training_date]"
                                max="{{ now()->format('Y-m') }}"
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
                                type="month"
                                name="training[${trainingIndex}][training_end_date]"
                                max="{{ now()->format('Y-m') }}"
                                class="${inputClass}"
                            >

                            <p class="mt-2 text-xs text-slate-500">
                                Select the month and year when the training or seminar started and ended.
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
            } else {
                specifyInput.classList.add('hidden');
                specifyInput.value = '';
            }
        }

        function toggleNeverExpires(checkboxEl) {
            const entry = checkboxEl.closest('.dynamic-entry');
            const validUntilWrapper = entry.querySelector('[data-role="valid-until-wrapper"]');
            const validUntilInput = validUntilWrapper.querySelector('input');

            if (checkboxEl.checked) {
                validUntilWrapper.classList.add('hidden');
                validUntilInput.value = '';
            } else {
                validUntilWrapper.classList.remove('hidden');
            }
        }

        function addEligibility() {
            const wrapper = document.getElementById('eligibilityWrapper');

            wrapper.insertAdjacentHTML('beforeend', `
                <div class="dynamic-entry rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <h4 class="font-black text-government-dark">
                            Eligibility Entry
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
                                class="${inputClass}"
                            >
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
                    </div>
                </div>
            `);

            eligibilityIndex++;
        }

        document.addEventListener('DOMContentLoaded', function () {
            addEducation();
            addExperience();
            addTraining();
            addEligibility();
        });
    </script>

</body>
</html>
