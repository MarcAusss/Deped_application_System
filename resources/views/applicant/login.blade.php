<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Login | DepEd Recruitment Portal</title>

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
    @include('partials.desktop-scale', ['fitHeight' => true])
</head>

<body class="min-h-screen bg-slate-50">

    <div class="mx-auto flex min-h-screen max-w-5xl flex-col items-center justify-center p-5">
        <div class="auth-card grid w-full grid-cols-1 overflow-hidden rounded-3xl bg-white lg:grid-cols-[2.3fr_2.7fr]">

            <div class="relative hidden min-h-[40rem] flex-col overflow-hidden lg:flex">
                <img
                    src="{{ asset('images/mayon.jpg') }}"
                    class="absolute left-1/2 top-0 h-full w-[260%] -translate-x-1/2 object-cover object-[center_15%]"
                    alt="SDO Albay">

                <div class="absolute inset-0 bg-gradient-to-b from-government-dark via-government-dark/85 to-government-dark"></div>

                <div class="relative z-10 flex h-full flex-col items-center px-8 pb-8 pt-14 text-center text-white">
                    <div class="flex h-32 w-32 items-center justify-center overflow-hidden rounded-full border-3 border-white/10 bg-white shadow-lg">
                        <img
                            src="{{ asset('images/depedalbay.png') }}"
                            alt="DepEd Logo"
                            class="h-40 w-40 shrink-0 translate-y-1 object-contain">
                    </div>

                    <p class="mt-5 text-base font-bold uppercase tracking-widest text-blue-200">
                        Welcome!
                    </p>

                    <h1 class="mt-1 text-4xl font-black leading-tight">
                        SDO Albay CARES
                    </h1>

                    <div class="mt-2 h-1 w-14 rounded-full bg-government-gold"></div>

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

                    <h2 class="text-3xl font-black text-government-dark">
                        Applicant Login
                    </h2>

                    <p class="mt-2 text-slate-500">
                        Access your account to apply and track your application.
                    </p>

                    @if (session('status'))
                        <div class="mt-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('applicant.login.submit') }}" class="mt-8 space-y-5">
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

                        <div class="text-right">
                            <a href="{{ route('applicant.password.request') }}" class="text-sm font-bold text-government-navy hover:underline">
                                Forgot password?
                            </a>
                        </div>

                        @if ($errors->any())
                            <div id="login-alert-box" class="flex items-start gap-2 rounded-xl border px-4 py-3 text-sm {{ session('loginWarning') ? 'border-amber-300 bg-amber-50 text-amber-700' : 'border-red-200 bg-red-50 text-red-600' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mt-0.5 h-5 w-5 shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-8.25 3.75h.008v.008h-.008v-.008Z" />
                                </svg>
                                <span id="login-error-text">
                                    @if(session('lockoutSeconds'))
                                        Too many failed login attempts. Please try again in <span id="lockout-countdown">{{ session('lockoutSeconds') }}</span> second(s).
                                    @else
                                        {{ $errors->first() }}
                                    @endif
                                </span>
                            </div>
                        @endif

                        <button
                            type="submit"
                            id="login-submit-btn"
                            class="auth-btn w-full rounded-xl py-3.5 text-lg font-bold text-white">
                            Login
                        </button>

                        <div class="flex items-center gap-3 text-xs font-bold uppercase tracking-wide text-slate-400">
                            <span class="h-px flex-1 bg-slate-200"></span>
                            or
                            <span class="h-px flex-1 bg-slate-200"></span>
                        </div>

                        <a
                            href="{{ route('applicant.register') }}"
                            class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-government-navy py-3.5 font-bold text-government-navy transition hover:bg-government-light">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                            </svg>
                            Create an account
                        </a>

                        <p class="text-center text-sm text-slate-500">
                            Don't have an account?
                            <a href="{{ route('applicant.register') }}" class="font-bold text-government-navy hover:underline">
                                Register here
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

        <p class="mt-2 text-xs text-slate-500">
            For issues and concerns, please contact:
            <a href="mailto:cares.support@depedalbay.com" class="font-bold text-government-navy hover:underline">cares.support@depedalbay.com</a>
        </p>
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

    @if(session('lockoutSeconds'))
        <script>
            (function () {
                var seconds = {{ (int) session('lockoutSeconds') }};
                var countdownEl = document.getElementById('lockout-countdown');
                var textEl = document.getElementById('login-error-text');
                var alertBox = document.getElementById('login-alert-box');
                var submitBtn = document.getElementById('login-submit-btn');
                var originalLabel = submitBtn ? submitBtn.textContent.trim() : null;

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                }

                var interval = setInterval(function () {
                    seconds--;

                    if (seconds <= 0) {
                        clearInterval(interval);

                        if (textEl) {
                            textEl.textContent = 'You can try logging in again now.';
                        }

                        if (alertBox) {
                            alertBox.classList.remove('border-red-200', 'bg-red-50', 'text-red-600', 'border-amber-300', 'bg-amber-50', 'text-amber-700');
                            alertBox.classList.add('border-green-300', 'bg-green-50', 'text-green-700');
                        }

                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                            submitBtn.textContent = originalLabel;
                        }

                        return;
                    }

                    if (countdownEl) {
                        countdownEl.textContent = seconds;
                    }
                }, 1000);
            })();
        </script>
    @endif

</body>

</html>
