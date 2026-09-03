<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | DepEd Recruitment Portal</title>

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

    <style>
        .auth-card {
            box-shadow:
                0 10px 30px rgba(0, 0, 0, 0.06),
                0 25px 60px rgba(0, 0, 0, 0.08);
        }

        .auth-input {
            width: 100%;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            border-radius: 12px;
            padding: .85rem 1rem .85rem 2.75rem;
            outline: none;
            transition: .2s ease;
            color: #0f172a;
        }

        .auth-input:focus {
            border-color: #123B6D;
            box-shadow: 0 0 0 4px rgba(18, 59, 109, 0.12);
        }

        .auth-icon {
            position: absolute;
            left: .9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .auth-btn {
            background: #123B6D;
            transition: .2s ease;
        }

        .auth-btn:hover {
            background: #1D4E89;
        }
    </style>
</head>

<body class="min-h-screen bg-slate-50">

    <div class="mx-auto flex min-h-screen max-w-5xl flex-col items-center justify-center p-5">
        <div class="auth-card grid w-full grid-cols-1 overflow-hidden rounded-3xl bg-white lg:grid-cols-[2fr_3fr]">

            <div class="relative hidden min-h-[640px] flex-col overflow-hidden lg:flex">
                <img
                    src="{{ asset('images/mayon.jpg') }}"
                    class="absolute left-1/2 top-0 h-full w-[260%] -translate-x-1/2 object-cover object-[center_15%]"
                    alt="SDO Albay">

                <div class="absolute inset-0 bg-gradient-to-b from-government-dark via-government-dark/85 to-government-dark"></div>

                <div class="relative z-10 flex h-full flex-col items-center px-8 pb-8 pt-10 text-center text-white">
                    <div class="flex h-28 w-28 items-center justify-center overflow-hidden rounded-full border-3 border-white/10 bg-white shadow-lg">
                        <img
                            src="{{ asset('images/depedalbay.png') }}"
                            alt="DepEd Logo"
                            class="h-36 w-36 shrink-0 translate-y-1 object-contain">
                    </div>

                    <p class="mt-5 text-xs font-bold uppercase tracking-widest text-blue-200">
                        Department of Education
                    </p>

                    <h1 class="mt-1 text-2xl font-black leading-tight">
                        SCHOOL DIVISION OFFICE<br>OF ALBAY
                    </h1>

                    <div class="mt-4 h-1 w-14 rounded-full bg-government-gold"></div>

                    <div class="mt-4 flex items-center justify-center gap-2 text-sm text-blue-100">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4 shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                        </svg>
                        <span>Lignon Hill, Bogtong, Legazpi City</span>
                    </div>

                    <div class="flex-1"></div>

                    <div class="w-full border-t border-white/10 pt-6">
                        <div class="flex flex-col items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6 shrink-0 text-government-gold">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                            </svg>

                            <p class="text-sm leading-relaxed text-blue-100">
                                Career Application &amp; Recruitment for Education Services (CARES) &mdash; building brighter futures through quality education and excellent service.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center bg-white px-8 py-12 sm:px-14">
                <div class="w-full">
                    <div class="mb-8 flex justify-center lg:hidden">
                        <div class="flex h-20 w-20 items-center justify-center rounded-full bg-government-light shadow">
                            <img
                                src="{{ asset('images/depedalbay.png') }}"
                                class="h-12 w-12 object-contain"
                                alt="Logo">
                        </div>
                    </div>

                    <a
                        href="{{ route('applicant.login') }}"
                        class="mb-4 inline-flex items-center gap-1.5 text-sm font-bold text-slate-500 hover:text-government-navy">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>
                        Back to Login
                    </a>

                    <h2 class="text-3xl font-black text-government-dark">
                        Forgot Password?
                    </h2>

                    <p class="mt-2 text-slate-500">
                        Enter the email address linked to your account and we'll send you a link to reset your password.
                    </p>

                    @if (session('status'))
                        <div class="mt-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('applicant.password.email') }}" class="mt-8 space-y-5">
                        @csrf

                        <div class="relative">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="auth-icon h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>

                            <input
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="Email Address"
                                required
                                autofocus
                                class="auth-input">
                        </div>

                        @if ($errors->any())
                            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <button
                            type="submit"
                            class="auth-btn w-full rounded-xl py-3.5 text-lg font-bold text-white">
                            Send Reset Link
                        </button>

                        <p class="text-center text-sm text-slate-500">
                            Remembered your password?
                            <a href="{{ route('applicant.login') }}" class="font-bold text-government-navy hover:underline">
                                Login here
                            </a>
                        </p>
                    </form>
                </div>
            </div>
        </div>

        <div class="mt-5 flex items-center gap-2 text-xs text-slate-500">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
            </svg>
            Your information is secure with us.
        </div>
    </div>

</body>

</html>
