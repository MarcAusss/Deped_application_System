<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Profile | DepEd Recruitment Portal</title>

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
</head>

<body class="flex min-h-screen flex-col bg-slate-50 text-slate-800">

    @include('applicant.partials.topbar')

    <div class="lg:flex lg:flex-1">
        @include('applicant.partials.sidebar')

        <div class="min-w-0 flex-1">
            @include('applicant.partials.mobile-nav')

    <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">

        @if(session('success'))
            <div class="mb-8 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-8 border-b border-slate-200 pb-6">
            <p class="text-sm font-bold uppercase tracking-widest text-government-blue">
                My Account
            </p>

            <h2 class="mt-2 text-3xl font-black text-government-dark">
                My Profile
            </h2>

            <p class="mt-2 text-slate-600">
                Manage your account name, email, and password.
            </p>
        </div>

        @php
            $activeTab = $errors->updatePassword->any() ? 'password' : 'account';
        @endphp

        <div class="flex flex-col gap-6 md:flex-row md:items-start">
            <nav class="flex shrink-0 gap-2 overflow-x-auto md:w-56 md:flex-col md:gap-1 md:overflow-visible">
                <button
                    type="button"
                    data-profile-tab="account"
                    class="profile-tab-link whitespace-nowrap rounded-lg px-4 py-2.5 text-left text-sm font-bold transition"
                >
                    Account Information
                </button>

                <button
                    type="button"
                    data-profile-tab="password"
                    class="profile-tab-link whitespace-nowrap rounded-lg px-4 py-2.5 text-left text-sm font-bold transition"
                >
                    Change Password
                </button>
            </nav>

            <div class="flex-1">

        <div data-profile-panel="account" class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <h3 class="mb-6 text-lg font-black text-government-dark">
                Account Information
            </h3>

            <form method="POST" action="{{ route('applicant.profile.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label class="mb-1 block text-sm font-bold text-slate-700">
                        Full Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $applicant->name) }}"
                        required
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20"
                    >

                    @error('name', 'updateProfile')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-bold text-slate-700">
                        Email Address
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="{{ old('email', $applicant->email) }}"
                        required
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20"
                    >

                    @error('email', 'updateProfile')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="rounded-xl bg-government-navy px-5 py-3 font-bold text-white transition hover:bg-government-blue"
                >
                    Save Changes
                </button>
            </form>
        </div>

        <div data-profile-panel="password" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <h3 class="mb-6 text-lg font-black text-government-dark">
                Change Password
            </h3>

            <form method="POST" action="{{ route('applicant.profile.password') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label class="mb-1 block text-sm font-bold text-slate-700">
                        Current Password
                    </label>

                    <input
                        type="password"
                        name="current_password"
                        required
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20"
                    >

                    @error('current_password', 'updatePassword')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-bold text-slate-700">
                        New Password
                    </label>

                    <input
                        type="password"
                        name="password"
                        required
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20"
                    >

                    @error('password', 'updatePassword')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-bold text-slate-700">
                        Confirm New Password
                    </label>

                    <input
                        type="password"
                        name="password_confirmation"
                        required
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20"
                    >
                </div>

                <button
                    type="submit"
                    class="rounded-xl bg-government-navy px-5 py-3 font-bold text-white transition hover:bg-government-blue"
                >
                    Update Password
                </button>
            </form>
        </div>

            </div>
        </div>
    </main>
        </div>
    </div>

    <footer class="border-t-4 border-government-gold bg-government-dark text-white">
        <div class="px-4 py-6 text-center text-sm text-slate-300 sm:px-6 lg:px-8">
            © {{ date('Y') }} Department of Education. All rights reserved.
        </div>
    </footer>

    <style>
        .profile-tab-link {
            color: #475569;
        }

        .profile-tab-link:hover {
            background: #f1f5f9;
        }

        .profile-tab-link.is-active {
            background: #123B6D;
            color: #ffffff;
        }
    </style>

    <script>
        (function () {
            const tabs = document.querySelectorAll('[data-profile-tab]');
            const panels = document.querySelectorAll('[data-profile-panel]');

            function activate(tabName) {
                tabs.forEach((tab) => {
                    tab.classList.toggle('is-active', tab.dataset.profileTab === tabName);
                });

                panels.forEach((panel) => {
                    panel.style.display = panel.dataset.profilePanel === tabName ? 'block' : 'none';
                });
            }

            tabs.forEach((tab) => {
                tab.addEventListener('click', () => activate(tab.dataset.profileTab));
            });

            activate(@js($activeTab));
        })();
    </script>

</body>
</html>
