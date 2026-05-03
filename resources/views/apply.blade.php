<!DOCTYPE html>
<html>
<head>
    <title>IER Application Form</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 p-10">

<div class="max-w-6xl mx-auto bg-white p-8 shadow rounded">

<h1 class="text-2xl font-bold">IER Application Form</h1>
<p class="text-gray-600 mb-6">Applying for: {{ $job->title }}</p>

<form method="POST" action="{{ route('apply.submit', $job) }}" enctype="multipart/form-data">
@csrf

{{-- ================= PERSONAL INFO ================= --}}
<h2 class="text-lg font-bold mt-4">Personal Information</h2>

<div class="grid grid-cols-2 gap-4 mt-2">
    <input name="full_name" placeholder="Full Name" class="border p-2">
    <input name="email" placeholder="Email" class="border p-2">
    <input name="phone_number" placeholder="Phone Number" class="border p-2">
    <input name="address" placeholder="Address" class="border p-2">
    <input name="disability" placeholder="Disability" class="border p-2">
    <input name="ethnic_group" placeholder="Ethnic Group" class="border p-2">
</div>

{{-- ================= EDUCATION ================= --}}
<h2 class="text-lg font-bold mt-6">Education</h2>

<div id="educationWrapper"></div>
<button type="button" onclick="addEducation()" class="text-blue-600 mt-2">+ Add Education</button>

{{-- ================= EXPERIENCE ================= --}}
<h2 class="text-lg font-bold mt-6">Experience</h2>

<div id="experienceWrapper"></div>
<button type="button" onclick="addExperience()" class="text-blue-600 mt-2">+ Add Experience</button>

{{-- ================= TRAINING ================= --}}
<h2 class="text-lg font-bold mt-6">Training</h2>

<div id="trainingWrapper"></div>
<button type="button" onclick="addTraining()" class="text-blue-600 mt-2">+ Add Training</button>

{{-- ================= DOCUMENTS ================= --}}
<h2 class="text-lg font-bold mt-6">Required Documents</h2>

<div class="grid grid-cols-2 gap-3 mt-2">

<input type="file" name="letter_of_intent">
<input type="file" name="tor_diploma">
<input type="file" name="prc_license">
<input type="file" name="eligibility">
<input type="file" name="training_certificates">
<input type="file" name="employment_records">
<input type="file" name="latest_appointment">
<input type="file" name="performance_rating">
<input type="file" name="cav">
<input type="file" name="movs">

</div>

{{-- ================= SUBMIT ================= --}}
<button class="bg-green-600 text-white px-6 py-2 mt-6 rounded">
    Submit Application
</button>

</form>
</div>

{{-- ================= REPEATERS ================= --}}
<script>
let eduIndex = 0;
let expIndex = 0;
let trainingIndex = 0;

function addEducation() {
    document.getElementById('educationWrapper').insertAdjacentHTML('beforeend', `
        <div class="grid grid-cols-4 gap-2 mt-2 border p-2 rounded bg-gray-50">
            <input name="education[${eduIndex}][level]" placeholder="Level" class="border p-1">
            <input name="education[${eduIndex}][school]" placeholder="School" class="border p-1">
            <input name="education[${eduIndex}][degree]" placeholder="Degree" class="border p-1">
            <input name="education[${eduIndex}][year_graduated]" placeholder="Year Graduated" class="border p-1">
            <button type="button" onclick="this.parentElement.remove()" class="text-red-600">X</button>
        </div>
    `);
    eduIndex++;
}

function addExperience() {
    document.getElementById('experienceWrapper').insertAdjacentHTML('beforeend', `
        <div class="grid grid-cols-3 gap-2 mt-2 border p-2 rounded bg-gray-50">
            <input name="experience[${expIndex}][title]" placeholder="Title" class="border p-1">
            <input name="experience[${expIndex}][company]" placeholder="Company" class="border p-1">
            <input name="experience[${expIndex}][years_months]" placeholder="Years/Months" class="border p-1">
            <button type="button" onclick="this.parentElement.remove()" class="text-red-600">X</button>
        </div>
    `);
    expIndex++;
}

function addTraining() {
    document.getElementById('trainingWrapper').insertAdjacentHTML('beforeend', `
        <div class="grid grid-cols-3 gap-2 mt-2 border p-2 rounded bg-gray-50">
            <input name="training[${trainingIndex}][title]" placeholder="Title" class="border p-1">
            <input name="training[${trainingIndex}][hours]" placeholder="Hours" class="border p-1">
            <input name="training[${trainingIndex}][details]" placeholder="Details" class="border p-1">
            <button type="button" onclick="this.parentElement.remove()" class="text-red-600">X</button>
        </div>
    `);
    trainingIndex++;
}
</script>

</body>
</html>