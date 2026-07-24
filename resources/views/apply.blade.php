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
                    <p class="text-xs font-bold uppercase tracking-widest text-government-gold">
                        Department of Education
                    </p>

                    <h1 class="text-lg font-black text-government-dark sm:text-xl">
                        Applicant Management System
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

    <section class="bg-gradient-to-r from-government-dark to-government-blue text-white">
        <div class="mx-auto max-w-5xl px-4 py-12 text-center sm:px-6 lg:px-8">
            <p class="text-sm font-bold uppercase tracking-widest text-yellow-300">
                Online Recruitment
            </p>

            <h2 class="mt-3 text-3xl font-black sm:text-4xl">
                Application Form
            </h2>

            <p class="mt-3 text-lg text-blue-100">
                Applying for:
                <span class="font-black text-white">
                    {{ $job->title }}
                </span>
            </p>
        </div>
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
                        <input
                            type="text"
                            name="education[${educationIndex}][level]"
                            placeholder="Education level"
                            class="${inputClass}"
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
                            name="experience[${experienceIndex}][years_months]"
                            placeholder="Years and months"
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
                            type="text"
                            name="training[${trainingIndex}][hours]"
                            placeholder="Number of hours"
                            class="${inputClass}"
                        >

                        <textarea
                            name="training[${trainingIndex}][details]"
                            placeholder="Training details"
                            rows="3"
                            class="${inputClass} md:col-span-2"
                        ></textarea>
                    </div>
                </div>
            `);

            trainingIndex++;
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
                        <input
                            type="text"
                            name="eligibility[${eligibilityIndex}][license_name]"
                            placeholder="Eligibility or license name"
                            class="${inputClass}"
                        >

                        <input
                            type="text"
                            name="eligibility[${eligibilityIndex}][rating]"
                            placeholder="Rating"
                            class="${inputClass}"
                        >

                        <input
                            type="date"
                            name="eligibility[${eligibilityIndex}][valid_until]"
                            class="${inputClass}"
                        >

                        <label class="flex items-center gap-3 rounded-xl border border-slate-300 bg-white px-4 py-3">
                            <input
                                type="checkbox"
                                name="eligibility[${eligibilityIndex}][never_expires]"
                                value="1"
                                class="h-5 w-5 rounded border-slate-300 text-government-navy"
                            >

                            <span class="text-sm font-semibold text-slate-700">
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
