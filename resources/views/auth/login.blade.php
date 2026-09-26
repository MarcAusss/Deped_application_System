<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Login</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>


        .login-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.85);
            box-shadow:
                0 10px 30px rgba(0, 0, 0, 0.08),
                0 25px 60px rgba(0, 0, 0, 0.10);
        }

        .field {
            position: relative;
        }

        .input {
            width: 100%;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 18px;
            padding: 1.3rem 1rem .7rem;
            outline: none;
            transition: .25s ease;
            color: #0f172a;
        }

        .input::placeholder {
            color: transparent;
        }

        .input:focus {
            border-color: #16a34a;
            background: white;
            box-shadow: 0 0 0 5px rgba(34, 197, 94, 0.12);
        }

        .floating-label {
            position: absolute;
            left: 1rem;
            top: 1rem;
            color: #64748b;
            font-size: .95rem;
            transition: .2s ease;
            pointer-events: none;
        }

        .input:focus+.floating-label,
        .input:not(:placeholder-shown)+.floating-label {
            top: .45rem;
            font-size: .72rem;
            color: #16a34a;
            font-weight: 600;
        }

        .login-btn {
            background: linear-gradient(to right, #16a34a, #10b981);
            transition: .3s ease;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(34, 197, 94, 0.25);
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

<body class="min-h-screen flex items-center justify-center p-5">


    <div
         class="login-card w-full max-w-7xl rounded-[2.2rem] overflow-hidden grid grid-cols-1 lg:grid-cols-[3fr_2fr]">


        <div class="relative hidden lg:block min-h-[43.75rem]">


            <img
                src="{{ asset('images/SDO-ALBAY.webp') }}"
               class="absolute inset-0 w-full h-full object-cover object-[42%_center]"
                alt="Background Image">


            <div class="absolute inset-0 bg-white/70"></div>


            <div class="relative z-10 flex flex-col items-center justify-start h-full px-10 pt-60 text-center">


                <div
                    class="w-32 h-32 rounded-full bg-white shadow-2xl flex items-center justify-center border border-white">


                    <img
                        src="{{ asset('images/depedalbay.png') }}"
                        alt="Logo"
                        class="w-30 h-30 object-contain">

                </div>

                <h1 class="text-4xl font-black text-gray-800 mt-8">
                    Career Application & Recruitment for Education Services (CARES)
                </h1>

                <p class="text-gray-700 mt-4 text-lg max-w-md leading-relaxed">
                    Welcome to the online recruitment and application management system.
                </p>

            </div>

        </div>


        <div class="bg-white px-8 md:px-16 py-14 flex items-center">

            <div class="w-full">


                <div class="lg:hidden flex justify-center mb-8">

                    <div
                        class="w-24 h-24 rounded-full bg-green-100 flex items-center justify-center shadow-lg">

                        <img
                            src="{{ asset('images/logo.png') }}"
                            class="w-14 h-14 object-contain"
                            alt="Logo">

                    </div>

                </div>

                <div class="mb-10">

                    <h2 class="text-4xl font-black text-[#123B6D]">
                        Welcome Back
                    </h2>

                    <p class="mt-3 text-lg text-[#1D4E89]">
                        Sign in to continue to your account
                    </p>

                </div>


                <form method="POST" action="/login" class="space-y-6">

                    @csrf

                    @if (session('status'))
                        <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                            {{ session('status') }}
                        </div>
                    @endif


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

                    <div class="text-right" hidden>
                        <a href="{{ route('password.request') }}" class="text-sm font-bold text-[#123B6D] hover:underline">
                            Forgot password?
                        </a>
                    </div>


                    @if ($errors->any())
                        <div
                            id="login-alert-box"
                            class="flex items-start gap-2 rounded-2xl border px-4 py-3 text-sm {{ session('loginWarning') ? 'border-amber-300 bg-amber-50 text-amber-700' : 'border-red-200 bg-red-50 text-red-600' }}">
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
                        class="auth-btn w-full text-white py-4 rounded-2xl font-bold text-lg">

                        Login

                    </button>

                </form>

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

    </div>

</body>

</html>
