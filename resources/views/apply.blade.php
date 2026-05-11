<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DepEd Application Form</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            
        }

        .card {
            background: white;
            border-radius: 28px;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.06);
            border: 1px solid #f1f5f9;
        }

        .field {
            position: relative;
        }

        .input {
            width: 100%;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 1.4rem 1rem 0.7rem;
            font-size: 0.95rem;
            color: #0f172a;
            outline: none;
            transition: all .25s ease;
        }

        .input::placeholder {
            color: transparent;
        }

        .input:focus {
            border-color: #22c55e;
            background: white;
            box-shadow: 0 0 0 5px rgba(34, 197, 94, 0.12);
        }

        .floating-label {
            position: absolute;
            left: 1rem;
            top: 1rem;
            font-size: 0.95rem;
            color: #64748b;
            transition: all .2s ease;
            pointer-events: none;
            background: transparent;
        }

        .input:focus+.floating-label,
        .input:not(:placeholder-shown)+.floating-label {
            top: 0.45rem;
            font-size: 0.72rem;
            color: #16a34a;
            font-weight: 600;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #111827;
        }

        .section-subtitle {
            color: #6b7280;
            margin-top: .3rem;
            font-size: .95rem;
        }

        .add-btn {
            background: #dcfce7;
            color: #15803d;
            padding: .8rem 1.2rem;
            border-radius: 18px;
            font-weight: 600;
            transition: .2s;
        }

        .add-btn:hover {
            background: #bbf7d0;
        }

        .remove-btn {
            color: #ef4444;
            font-size: .9rem;
            font-weight: 600;
        }

        .remove-btn:hover {
            color: #b91c1c;
        }

        .file-upload {
            border: 2px dashed #d1d5db;
            border-radius: 20px;
            padding: 1.2rem;
            background: #f9fafb;
            transition: .3s;
        }

        .file-upload:hover {
            border-color: #22c55e;
            background: #f0fdf4;
        }

        .file-input {
            width: 100%;
            font-size: .9rem;
            color: #475569;
        }

        .file-input::file-selector-button {
            background: #16a34a;
            color: white;
            border: none;
            padding: .7rem 1rem;
            border-radius: 14px;
            cursor: pointer;
            margin-right: 1rem;
            transition: .2s;
        }

        .file-input::file-selector-button:hover {
            background: #15803d;
        }

        .submit-btn {
            background: linear-gradient(to right, #16a34a, #10b981);
            color: white;
            font-weight: 700;
            padding: 1rem 3rem;
            border-radius: 22px;
            transition: .3s;
            box-shadow: 0 10px 25px rgba(34, 197, 94, 0.25);
        }

        .submit-btn:hover {
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 15px 30px rgba(34, 197, 94, 0.35);
        }
    </style>
</head>

<body class="min-h-screen py-10 px-4">

    <div class="max-w-7xl mx-auto">

        <!-- HEADER -->
        <div class="text-center mb-10">

            <div
                class="w-36 h-36 mx-auto rounded-full bg-green-100 flex items-center justify-center text-green-700 text-3xl font-black shadow-lg">
                DepEd
            </div>

            <h1 class="text-4xl font-black text-gray-800 mt-5">
                Application Form
            </h1>

            <p class="text-gray-500 text-lg mt-2">
                Applying for:
                <span class="text-green-700 font-bold">
                    {{ $job->title }}
                </span>
            </p>

        </div>

        <form method="POST"
            action="{{ route('apply.submit', $job) }}"
            enctype="multipart/form-data"
            class="space-y-8">

            @csrf

            <!-- PERSONAL INFO -->
            <div class="card p-8">

                <div class="mb-8">
                    <h2 class="section-title">
                        Personal Information
                    </h2>

                    <p class="section-subtitle">
                        Please provide your complete personal details.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div class="field">
                        <input type="text" name="full_name" placeholder=" " class="input">
                        <label class="floating-label">Full Name</label>
                    </div>

                    <div class="field">
                        <input type="email" name="email" placeholder=" " class="input">
                        <label class="floating-label">Email Address</label>
                    </div>

                    <div class="field">
                        <input type="text" name="phone_number" placeholder=" " class="input">
                        <label class="floating-label">Phone Number</label>
                    </div>

                    <div class="field">
                        <input type="text" name="address" placeholder=" " class="input">
                        <label class="floating-label">Complete Address</label>
                    </div>

                    <div class="field">
                        <input type="text" name="disability" placeholder=" " class="input">
                        <label class="floating-label">Disability (Optional)</label>
                    </div>

                    <div class="field">
                        <input type="text" name="ethnic_group" placeholder=" " class="input">
                        <label class="floating-label">Ethnic Group (Optional)</label>
                    </div>

                </div>

            </div>

            <!-- EDUCATION -->
            <div class="card p-8">

                <div class="flex items-center justify-between flex-wrap gap-4 mb-8">

                    <div>
                        <h2 class="section-title">
                            Educational Background
                        </h2>

                        <p class="section-subtitle">
                            Add all relevant educational information.
                        </p>
                    </div>

                    <button type="button"
                        onclick="addEducation()"
                        class="add-btn">
                        + Add Education
                    </button>

                </div>

                <div id="educationWrapper" class="space-y-5"></div>

            </div>

            <!-- EXPERIENCE -->
            <div class="card p-8">

                <div class="flex items-center justify-between flex-wrap gap-4 mb-8">

                    <div>
                        <h2 class="section-title">
                            Work Experience
                        </h2>

                        <p class="section-subtitle">
                            Include your previous employment history.
                        </p>
                    </div>

                    <button type="button"
                        onclick="addExperience()"
                        class="add-btn">
                        + Add Experience
                    </button>

                </div>

                <div id="experienceWrapper" class="space-y-5"></div>

            </div>

            <!-- TRAINING -->
            <div class="card p-8">

                <div class="flex items-center justify-between flex-wrap gap-4 mb-8">

                    <div>
                        <h2 class="section-title">
                            Trainings & Seminars
                        </h2>

                        <p class="section-subtitle">
                            Add relevant seminars, workshops, and trainings.
                        </p>
                    </div>

                    <button type="button"
                        onclick="addTraining()"
                        class="add-btn">
                        + Add Training
                    </button>

                </div>

                <div id="trainingWrapper" class="space-y-5"></div>

            </div>

            <!-- DOCUMENTS -->
            <div class="card p-8">

                <div class="mb-8">

                    <h2 class="section-title">
                        Required Documents
                    </h2>

                    <p class="section-subtitle">
                        Upload the required supporting documents.
                    </p>

                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    <div class="file-upload">
                        <label class="block font-semibold text-gray-700 mb-3">
                            Letter of Intent
                        </label>
                        <input type="file" name="letter_of_intent" class="file-input">
                    </div>

                    <div class="file-upload">
                        <label class="block font-semibold text-gray-700 mb-3">
                            TOR / Diploma
                        </label>
                        <input type="file" name="tor_diploma" class="file-input">
                    </div>

                    <div class="file-upload">
                        <label class="block font-semibold text-gray-700 mb-3">
                            PRC License
                        </label>
                        <input type="file" name="prc_license" class="file-input">
                    </div>

                    <div class="file-upload">
                        <label class="block font-semibold text-gray-700 mb-3">
                            Eligibility
                        </label>
                        <input type="file" name="eligibility" class="file-input">
                    </div>

                    <div class="file-upload">
                        <label class="block font-semibold text-gray-700 mb-3">
                            Training Certificates
                        </label>
                        <input type="file" name="training_certificates" class="file-input">
                    </div>

                    <div class="file-upload">
                        <label class="block font-semibold text-gray-700 mb-3">
                            Employment Records
                        </label>
                        <input type="file" name="employment_records" class="file-input">
                    </div>

                    <div class="file-upload">
                        <label class="block font-semibold text-gray-700 mb-3">
                            Latest Appointment
                        </label>
                        <input type="file" name="latest_appointment" class="file-input">
                    </div>

                    <div class="file-upload">
                        <label class="block font-semibold text-gray-700 mb-3">
                            Performance Rating
                        </label>
                        <input type="file" name="performance_rating" class="file-input">
                    </div>

                    <div class="file-upload">
                        <label class="block font-semibold text-gray-700 mb-3">
                            CAV
                        </label>
                        <input type="file" name="cav" class="file-input">
                    </div>

                    <div class="file-upload">
                        <label class="block font-semibold text-gray-700 mb-3">
                            MOVs
                        </label>
                        <input type="file" name="movs" class="file-input">
                    </div>

                </div>

            </div>

            <!-- SUBMIT -->
            <div class="text-center">

                <button class="submit-btn">
                    Submit Application
                </button>

            </div>

        </form>

    </div>

    <!-- SCRIPT -->
    <script>

        let eduIndex = 0;
        let expIndex = 0;
        let trainingIndex = 0;

        function addEducation() {

            document.getElementById('educationWrapper')
                .insertAdjacentHTML('beforeend', `

            <div class="border border-gray-200 rounded-3xl p-6 bg-gray-50 relative">

                <button type="button"
                    onclick="this.parentElement.remove()"
                    class="absolute top-5 right-5 remove-btn">
                    Remove
                </button>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">

                    <div class="field">
                        <input name="education[${eduIndex}][level]"
                            placeholder=" "
                            class="input">
                        <label class="floating-label">
                            Level
                        </label>
                    </div>

                    <div class="field">
                        <input name="education[${eduIndex}][school]"
                            placeholder=" "
                            class="input">
                        <label class="floating-label">
                            School
                        </label>
                    </div>

                    <div class="field">
                        <input name="education[${eduIndex}][degree]"
                            placeholder=" "
                            class="input">
                        <label class="floating-label">
                            Degree / Course
                        </label>
                    </div>

                    <div class="field">
                        <input name="education[${eduIndex}][year_graduated]"
                            placeholder=" "
                            class="input">
                        <label class="floating-label">
                            Year Graduated
                        </label>
                    </div>

                </div>

            </div>

        `);

            eduIndex++;
        }

        function addExperience() {

            document.getElementById('experienceWrapper')
                .insertAdjacentHTML('beforeend', `

            <div class="border border-gray-200 rounded-3xl p-6 bg-gray-50 relative">

                <button type="button"
                    onclick="this.parentElement.remove()"
                    class="absolute top-5 right-5 remove-btn">
                    Remove
                </button>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

                    <div class="field">
                        <input name="experience[${expIndex}][title]"
                            placeholder=" "
                            class="input">
                        <label class="floating-label">
                            Position Title
                        </label>
                    </div>

                    <div class="field">
                        <input name="experience[${expIndex}][company]"
                            placeholder=" "
                            class="input">
                        <label class="floating-label">
                            Company / Office
                        </label>
                    </div>

                    <div class="field">
                        <input name="experience[${expIndex}][years_months]"
                            placeholder=" "
                            class="input">
                        <label class="floating-label">
                            Years / Months
                        </label>
                    </div>

                </div>

            </div>

        `);

            expIndex++;
        }

        function addTraining() {

            document.getElementById('trainingWrapper')
                .insertAdjacentHTML('beforeend', `

            <div class="border border-gray-200 rounded-3xl p-6 bg-gray-50 relative">

                <button type="button"
                    onclick="this.parentElement.remove()"
                    class="absolute top-5 right-5 remove-btn">
                    Remove
                </button>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

                    <div class="field">
                        <input name="training[${trainingIndex}][title]"
                            placeholder=" "
                            class="input">
                        <label class="floating-label">
                            Training Title
                        </label>
                    </div>

                    <div class="field">
                        <input name="training[${trainingIndex}][hours]"
                            placeholder=" "
                            class="input">
                        <label class="floating-label">
                            No. of Hours
                        </label>
                    </div>

                    <div class="field">
                        <input name="training[${trainingIndex}][details]"
                            placeholder=" "
                            class="input">
                        <label class="floating-label">
                            Details
                        </label>
                    </div>

                </div>

            </div>

        `);

            trainingIndex++;
        }

        // DEFAULT FIELDS
        addEducation();
        addExperience();
        addTraining();

    </script>

</body>

</html>