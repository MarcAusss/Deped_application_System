<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SDO Albay CARES</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        .landing-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.9);
            box-shadow:
                0 10px 30px rgba(0, 0, 0, 0.08),
                0 25px 60px rgba(0, 0, 0, 0.10);
        }

        .choice {
            border: 1px solid #e2e8f0;
            transition: .2s ease;
        }

        .choice:hover {
            border-color: #123B6D;
            box-shadow: 0 12px 25px rgba(18, 59, 109, 0.12);
            transform: translateY(-2px);
        }

        .choice .go {
            transition: .2s ease;
        }

        .choice:hover .go {
            transform: translateX(3px);
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-5 bg-slate-50">

    <div class="landing-card w-full max-w-3xl rounded-[32px] overflow-hidden">

        <div class="flex flex-col items-center px-8 pt-10 pb-10 text-center" style="background: linear-gradient(150deg, #0B2545, #123B6D);">

            <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-full border-3 border-white/10 bg-white shadow-lg">
                <img
                    src="{{ asset('images/depedalbay.png') }}"
                    alt="DepEd SDO Albay Logo"
                    class="h-32 w-32 shrink-0 translate-y-1 object-contain">
            </div>

            <p class="mt-5 text-xs font-bold uppercase tracking-widest text-[#D4A017]">
                Department of Education &middot; Schools Division Office of Albay
            </p>

            <h1 class="mt-2 text-3xl md:text-4xl font-black text-white">
                SDO Albay CARES
            </h1>

            <p class="mt-3 text-blue-100/80 max-w-lg">
                Career Application &amp; Recruitment for Education Services
            </p>

        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 px-8 py-10">

            <a href="{{ route('jobs.index') }}" class="choice group rounded-2xl bg-white p-6 flex flex-col">
                <div class="w-12 h-12 rounded-xl bg-[#EAF2F8] flex items-center justify-center text-[#123B6D]">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                </div>
                <h2 class="mt-4 text-lg font-black text-[#123B6D]">Applicant Portal</h2>
                <p class="mt-1.5 text-sm text-slate-500 leading-relaxed flex-1">
                    Browse open positions, apply, and track your application status.
                </p>
                <span class="go mt-4 inline-flex items-center gap-1.5 text-sm font-bold text-[#123B6D]">
                    Browse open positions
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </span>
            </a>

            <a href="{{ route('login') }}" class="choice group rounded-2xl bg-white p-6 flex flex-col">
                <div class="w-12 h-12 rounded-xl bg-[#FBF3E3] flex items-center justify-center text-[#A9760D]">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                </div>
                <h2 class="mt-4 text-lg font-black text-[#123B6D]">Office System</h2>
                <p class="mt-1.5 text-sm text-slate-500 leading-relaxed flex-1">
                    For DepEd staff &mdash; recruitment administration and application evaluation.
                </p>
                <span class="go mt-4 inline-flex items-center gap-1.5 text-sm font-bold text-[#123B6D]">
                    Staff login
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </span>
            </a>

        </div>

    </div>

</body>

</html>
