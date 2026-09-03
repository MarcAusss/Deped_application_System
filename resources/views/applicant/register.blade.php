<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Registration | DepEd Recruitment Portal</title>

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

        .auth-toggle {
            position: absolute;
            right: .9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            cursor: pointer;
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

            <div class="relative hidden min-h-[700px] flex-col overflow-hidden lg:flex">
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

                    <div class="w-full space-y-5 border-t border-white/10 pt-6">
                        <div class="flex flex-col items-center gap-2">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/10">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5 text-government-gold">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                </svg>
                            </div>

                            <div>
                                <p class="font-bold text-white">Secure</p>
                                <p class="text-sm text-blue-100">Your information is protected.</p>
                            </div>
                        </div>

                        <div class="flex flex-col items-center gap-2">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/10">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5 text-government-gold">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                                </svg>
                            </div>

                            <div>
                                <p class="font-bold text-white">Easy</p>
                                <p class="text-sm text-blue-100">Simple steps to get started.</p>
                            </div>
                        </div>

                        <div class="flex flex-col items-center gap-2">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/10">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5 text-government-gold">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                                </svg>
                            </div>

                            <div>
                                <p class="font-bold text-white">Accessible</p>
                                <p class="text-sm text-blue-100">Apply anytime, anywhere.</p>
                            </div>
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

                    <h2 class="text-3xl font-black text-government-dark">
                        Applicant Registration
                    </h2>

                    <p class="mt-2 text-slate-500">
                        Fill out the form below to create your account.
                    </p>

                    <form method="POST" action="{{ route('applicant.register.submit') }}" class="mt-8 space-y-5">
                        @csrf

                        <div class="relative">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="auth-icon h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>

                            <input
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                placeholder="Full Name"
                                required
                                class="auth-input">
                        </div>

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
                                class="auth-input">
                        </div>

                        <div class="relative">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="auth-icon h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>

                            <input
                                id="password"
                                type="password"
                                name="password"
                                placeholder="Password"
                                required
                                class="auth-input">

                            <button type="button" class="auth-toggle" onclick="toggleAuthPassword('password', this)" aria-label="Show password">
                                <svg class="icon-eye h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <svg class="icon-eye-slash hidden h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>

                        <div class="relative">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="auth-icon h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>

                            <input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                placeholder="Confirm Password"
                                required
                                class="auth-input">

                            <button type="button" class="auth-toggle" onclick="toggleAuthPassword('password_confirmation', this)" aria-label="Show password">
                                <svg class="icon-eye h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <svg class="icon-eye-slash hidden h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>

                        @if ($errors->any())
                            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <button
                            type="submit"
                            class="auth-btn w-full rounded-xl py-3.5 text-lg font-bold text-white">
                            Register
                        </button>

                        <div class="flex items-center gap-3 text-xs font-bold uppercase tracking-wide text-slate-400">
                            <span class="h-px flex-1 bg-slate-200"></span>
                            or
                            <span class="h-px flex-1 bg-slate-200"></span>
                        </div>

                        <a
                            href="{{ route('applicant.login') }}"
                            class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-government-navy py-3.5 font-bold text-government-navy transition hover:bg-government-light">
                            Login instead
                        </a>

                        <p class="text-center text-sm text-slate-500">
                            Already have an account?
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

    <script>
        function toggleAuthPassword(inputId, button) {
            const input = document.getElementById(inputId);
            const willShow = input.type === 'password';
            input.type = willShow ? 'text' : 'password';

            button.querySelector('.icon-eye').classList.toggle('hidden', willShow);
            button.querySelector('.icon-eye-slash').classList.toggle('hidden', !willShow);
            button.setAttribute('aria-label', willShow ? 'Hide password' : 'Show password');
        }
    </script>

</body>

</html>
