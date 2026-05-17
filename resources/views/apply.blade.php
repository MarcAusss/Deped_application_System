<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DepEd Application Form</title>

    <!-- Tailwind Compilation via CDN or Local Layer Integration -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Laravel Vite Bundler Compilation Directive -->
    @vite(['resources/css/apply.css', 'resources/js/apply.js'])
</head>
<body class="min-h-screen py-10 px-4 bg-slate-50">

    <div class="max-w-7xl mx-auto">

        <!-- HEADER -->
        <div class="text-center mb-10">
            <div class="w-36 h-36 mx-auto rounded-full bg-green-100 flex items-center justify-center text-green-700 text-3xl font-black shadow-lg">
                DepEd
            </div>
            <h1 class="text-4xl font-black text-gray-800 mt-5">Application Form</h1>
            <p class="text-gray-500 text-lg mt-2">
                Applying for: <span class="text-green-700 font-bold">{{ $job->title }}</span>
            </p>
        </div>

        <form method="POST" action="{{ route('apply.submit', $job) }}" enctype="multipart/form-data" class="space-y-8">
            @csrf

            <!-- PERSONAL INFO -->
            <div class="form-card p-8">
                <div class="mb-8">
                    <h2 class="section-title">Personal Information</h2>
                    <p class="section-subtitle">Please provide your complete personal details.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-field">
                        <input type="text" name="full_name" placeholder=" " class="form-input">
                        <label class="floating-label">Full Name</label>
                    </div>
                    <div class="form-field">
                        <input type="email" name="email" placeholder=" " class="form-input">
                        <label class="floating-label">Email Address</label>
                    </div>
                    <div class="form-field">
                        <input type="text" name="phone_number" placeholder=" " class="form-input">
                        <label class="floating-label">Phone Number</label>
                    </div>
                    <div class="form-field">
                        <input type="text" name="address" placeholder=" " class="form-input">
                        <label class="floating-label">Complete Address</label>
                    </div>
                    <div class="form-field">
                        <input type="text" name="disability" placeholder=" " class="form-input">
                        <label class="floating-label">Disability (Optional)</label>
                    </div>
                    <div class="form-field">
                        <input type="text" name="ethnic_group" placeholder=" " class="form-input">
                        <label class="floating-label">Ethnic Group (Optional)</label>
                    </div>
                </div>
            </div>

            <!-- EDUCATION -->
            <div class="form-card p-8">
                <div class="flex items-center justify-between flex-wrap gap-4 mb-8">
                    <div>
                        <h2 class="section-title">Educational Background</h2>
                        <p class="section-subtitle">Add all relevant educational information.</p>
                    </div>
                    <button type="button" onclick="addEducation()" class="add-btn">+ Add Education</button>
                </div>
                <div id="educationWrapper" class="space-y-5"></div>
            </div>

            <!-- EXPERIENCE -->
            <div class="form-card p-8">
                <div class="flex items-center justify-between flex-wrap gap-4 mb-8">
                    <div>
                        <h2 class="section-title">Work Experience</h2>
                        <p class="section-subtitle">Include your previous employment history.</p>
                    </div>
                    <button type="button" onclick="addExperience()" class="add-btn">+ Add Experience</button>
                </div>
                <div id="experienceWrapper" class="space-y-5"></div>
            </div>

            <!-- TRAINING -->
            <div class="form-card p-8">
                <div class="flex items-center justify-between flex-wrap gap-4 mb-8">
                    <div>
                        <h2 class="section-title">Trainings & Seminars</h2>
                        <p class="section-subtitle">Add relevant seminars, workshops, and trainings.</p>
                    </div>
                    <button type="button" onclick="addTraining()" class="add-btn">+ Add Training</button>
                </div>
                <div id="trainingWrapper" class="space-y-5"></div>
            </div>

            <!-- ELIGIBILITY -->
            <div class="form-card p-8">
                <div class="flex items-center justify-between flex-wrap gap-4 mb-8">
                    <div>
                        <h2 class="section-title">Eligibility / Licenses</h2>
                        <p class="section-subtitle">
                            Add PRC, Civil Service, LET, NCII, or other eligibilities.
                        </p>
                    </div>

                    <button type="button"
                        onclick="addEligibility()"
                        class="add-btn">
                        + Add Eligibility
                    </button>
                </div>

                <div id="eligibilityWrapper" class="space-y-5"></div>
            </div>
            <!-- DOCUMENTS -->
            <div class="form-card p-8">
                <div class="mb-8">
                    <h2 class="section-title">Required Documents</h2>
                    <p class="section-subtitle">Upload the required supporting documents.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    @foreach([
                        'letter_of_intent' => 'Letter of Intent',
                        'tor_diploma' => 'TOR / Diploma',
                        'prc_license' => 'PRC License',
                        'eligibility_file' => 'Eligibility',
                        'training_certificates' => 'Training Certificates',
                        'employment_records' => 'Employment Records',
                        'latest_appointment' => 'Latest Appointment',
                        'performance_rating' => 'Performance Rating',
                        'cav' => 'CAV',
                        'movs' => 'MOVs'
                    ] as $key => $label)
                        <div class="file-upload">
                            <label class="block font-semibold text-gray-700 mb-3">{{ $label }}</label>
                            <input type="file" name="{{ $key }}" class="file-input">
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- SUBMIT -->
            <div class="text-center">
                <button class="submit-btn">Submit Application</button>
            </div>
        </form>
    </div>

</body>
</html>