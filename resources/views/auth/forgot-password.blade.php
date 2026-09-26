<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | System Login</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        .login-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.85);
            box-shadow:
                0 10px 30px rgba(0, 0, 0, 0.08),
                0 25px 60px rgba(0, 0, 0, 0.10);
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

    @include('partials.desktop-scale')
</head>

<body class="min-h-screen flex items-center justify-center p-5">

    <div
         class="login-card w-full max-w-7xl rounded-[35px] overflow-hidden grid grid-cols-1 lg:grid-cols-[3fr_2fr]">

        <div class="relative hidden lg:block min-h-[700px]">

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
                    <div class="w-24 h-24 rounded-full bg-green-100 flex items-center justify-center shadow-lg">
                        <img
                            src="{{ asset('images/logo.png') }}"
                            class="w-14 h-14 object-contain"
                            alt="Logo">
                    </div>
                </div>

                <a
                    href="{{ route('login') }}"
                    class="mb-4 inline-flex items-center gap-1.5 text-sm font-bold text-slate-500 hover:text-[#123B6D]">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    Back to Login
                </a>

                <div class="mb-10">
                    <h2 class="text-4xl font-black text-[#123B6D]">
                        Forgot Password?
                    </h2>

                    <p class="mt-3 text-lg text-[#1D4E89]">
                        Enter your email and we'll send you a reset link.
                    </p>
                </div>

                @if (session('status'))
                    <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" class="space-y-6">

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
                        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <button
                        type="submit"
                        class="auth-btn w-full text-white py-4 rounded-2xl font-bold text-lg">
                        Send Reset Link
                    </button>

                </form>

            </div>

        </div>

    </div>

</body>

</html>
