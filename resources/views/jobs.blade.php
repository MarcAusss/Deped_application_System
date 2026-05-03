<!DOCTYPE html>
<html>
<head>
    <title>Job Positions</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-10">

<h1 class="text-2xl font-bold mb-6">Available Job Positions</h1>

@foreach($jobs as $job)
    <div class="bg-white p-4 mb-4 shadow rounded">
        <h2 class="text-xl font-semibold">{{ $job->title }}</h2>
        <p class="text-gray-600">{{ $job->description }}</p>

        <a href="{{ route('apply.form', $job) }}"
           class="inline-block mt-3 bg-blue-600 text-white px-4 py-2 rounded">
            Apply Now
        </a>
    </div>
@endforeach

</body>
</html>