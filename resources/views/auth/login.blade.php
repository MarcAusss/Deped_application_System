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
    </style>

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

                    <div
                        class="w-24 h-24 rounded-full bg-green-100 flex items-center justify-center shadow-lg">

                        <img
                            src="{{ asset('images/logo.png') }}"
                            class="w-14 h-14 object-contain"
                            alt="Logo">

                    </div>

                </div>

                <div class="mb-10">

                    <h2 class="text-4xl font-black text-gray-800">
                        Welcome Back
                    </h2>

                    <p class="text-gray-500 mt-3 text-lg">
                        Sign in to continue to your account
                    </p>

                </div>

                 
                <form method="POST" action="/login" class="space-y-6">

                    @csrf

                     
                    <div class="field">

                        <input
                            type="email"
                            name="email"
                            placeholder=" "
                            required
                            class="input">

                        <label class="floating-label">
                            Email Address
                        </label>

                    </div>

                     
                    <div class="field">

                        <input
                            type="password"
                            name="password"
                            placeholder=" "
                            required
                            class="input">

                        <label class="floating-label">
                            Password
                        </label>

                    </div>

                     
                    <div class="flex items-center justify-between text-sm">

                        <label class="flex items-center gap-2 text-gray-600">

                            <input
                                type="checkbox"
                                class="rounded border-gray-300 text-green-600 focus:ring-green-500">

                            Remember me

                        </label>

                        <a href="#"
                            class="text-green-600 hover:text-green-700 font-medium">
                            Forgot Password?
                        </a>

                    </div>

                     
                    @if ($errors->any())
                        <div
                            class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-2xl text-sm">
                            {{ $errors->first() }}
                        </div>
                    @endif

                     
                    <button
                        type="submit"
                        class="login-btn w-full text-white py-4 rounded-2xl font-bold text-lg">

                        Login to System

                    </button>

                </form>

            </div>

        </div>

    </div>

</body>

</html>