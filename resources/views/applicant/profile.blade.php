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

        @if(session('error'))
            <div class="mb-8 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
                {{ session('error') }}
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
                Manage your personal information and password.
            </p>
        </div>

        @php
            $activeTab = 'personal';
            if ($errors->updatePassword->any()) {
                $activeTab = 'password';
            }
        @endphp

        <div class="flex flex-col gap-6 md:flex-row md:items-start">
            <nav class="flex shrink-0 gap-2 overflow-x-auto md:w-56 md:flex-col md:gap-1 md:overflow-visible">
                <button
                    type="button"
                    data-profile-tab="personal"
                    class="profile-tab-link whitespace-nowrap rounded-lg px-4 py-2.5 text-left text-sm font-bold transition"
                >
                    Personal Information
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

        <div data-profile-panel="personal" class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <h3 class="mb-1 text-lg font-black text-government-dark">
                Personal Information
            </h3>

            <p class="mb-6 text-sm text-slate-500">
                This is the personal information you provided on your most recent application.
            </p>

            @if($personalInfo && $personalInfoApplication->status === 'pending')
                @php
                    $selectedSex = old('sex', $personalInfo->sex);
                    $selectedCivilStatus = old('civil_status', $personalInfo->civil_status);
                @endphp

                <form method="POST" action="{{ route('applicant.profile.personalInfo.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-bold text-slate-700">Full Name</label>
                            <input
                                type="text"
                                name="full_name"
                                value="{{ old('full_name', $personalInfo->full_name) }}"
                                required
                                class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20"
                            >
                            @error('full_name', 'updatePersonalInfo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-bold text-slate-700">Email Address</label>
                            <input
                                type="email"
                                name="email"
                                value="{{ old('email', $personalInfo->email) }}"
                                required
                                class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20"
                            >
                            @error('email', 'updatePersonalInfo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-bold text-slate-700">Phone Number</label>
                            <input
                                type="text"
                                name="phone_number"
                                value="{{ old('phone_number', $personalInfo->phone) }}"
                                class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20"
                            >
                            @error('phone_number', 'updatePersonalInfo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-bold text-slate-700">Complete Address</label>
                            <input
                                type="text"
                                name="address"
                                value="{{ old('address', $personalInfo->address) }}"
                                class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20"
                            >
                            @error('address', 'updatePersonalInfo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-bold text-slate-700">Birth Date</label>
                            <input
                                type="date"
                                name="birth_date"
                                value="{{ old('birth_date', $personalInfo->birth_date?->toDateString()) }}"
                                max="{{ now()->toDateString() }}"
                                class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20"
                            >
                            @error('birth_date', 'updatePersonalInfo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-bold text-slate-700">Sex</label>
                            <select
                                name="sex"
                                class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20"
                            >
                                <option value="">Select sex</option>
                                <option value="Male" @selected($selectedSex === 'Male')>Male</option>
                                <option value="Female" @selected($selectedSex === 'Female')>Female</option>
                                <option value="Prefer not to say" @selected($selectedSex === 'Prefer not to say')>Prefer not to say</option>
                            </select>
                            @error('sex', 'updatePersonalInfo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-bold text-slate-700">Civil Status</label>
                            <select
                                name="civil_status"
                                class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20"
                            >
                                <option value="">Select civil status</option>
                                @foreach(['Single', 'Married', 'Widowed', 'Separated', 'Other'] as $civilStatus)
                                    <option value="{{ $civilStatus }}" @selected($selectedCivilStatus === $civilStatus)>
                                        {{ $civilStatus }}
                                    </option>
                                @endforeach
                            </select>
                            @error('civil_status', 'updatePersonalInfo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-bold text-slate-700">Religion</label>
                            <input
                                type="text"
                                name="religion"
                                value="{{ old('religion', $personalInfo->religion) }}"
                                class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20"
                            >
                            @error('religion', 'updatePersonalInfo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-bold text-slate-700">Disability (if any)</label>
                            <input
                                type="text"
                                name="disability"
                                value="{{ old('disability', $personalInfo->disability) }}"
                                class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20"
                            >
                            @error('disability', 'updatePersonalInfo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-bold text-slate-700">Ethnic Group</label>
                            <input
                                type="text"
                                name="ethnic_group"
                                value="{{ old('ethnic_group', $personalInfo->ethnic_group) }}"
                                class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-government-navy focus:ring-2 focus:ring-government-navy/20"
                            >
                            @error('ethnic_group', 'updatePersonalInfo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="rounded-xl bg-government-navy px-5 py-3 font-bold text-white transition hover:bg-government-blue"
                    >
                        Save Changes
                    </button>
                </form>
            @elseif($personalInfo)
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-lg border border-slate-200 px-4 py-2.5">
                        <p class="text-sm font-bold text-slate-700">Full Name</p>
                        <span class="text-slate-600">{{ $personalInfo->full_name ?? '—' }}</span>
                    </div>

                    <div class="rounded-lg border border-slate-200 px-4 py-2.5">
                        <p class="text-sm font-bold text-slate-700">Email Address</p>
                        <span class="text-slate-600">{{ $personalInfo->email ?? '—' }}</span>
                    </div>

                    <div class="rounded-lg border border-slate-200 px-4 py-2.5">
                        <p class="text-sm font-bold text-slate-700">Phone Number</p>
                        <span class="text-slate-600">{{ $personalInfo->phone ?? '—' }}</span>
                    </div>

                    <div class="rounded-lg border border-slate-200 px-4 py-2.5">
                        <p class="text-sm font-bold text-slate-700">Address</p>
                        <span class="text-slate-600">{{ $personalInfo->address ?? '—' }}</span>
                    </div>

                    <div class="rounded-lg border border-slate-200 px-4 py-2.5">
                        <p class="text-sm font-bold text-slate-700">Birth Date</p>
                        <span class="text-slate-600">{{ $personalInfo->birth_date?->format('M d, Y') ?? '—' }}</span>
                    </div>

                    <div class="rounded-lg border border-slate-200 px-4 py-2.5">
                        <p class="text-sm font-bold text-slate-700">Sex</p>
                        <span class="text-slate-600">{{ $personalInfo->sex ?? '—' }}</span>
                    </div>

                    <div class="rounded-lg border border-slate-200 px-4 py-2.5">
                        <p class="text-sm font-bold text-slate-700">Civil Status</p>
                        <span class="text-slate-600">{{ $personalInfo->civil_status ?? '—' }}</span>
                    </div>

                    <div class="rounded-lg border border-slate-200 px-4 py-2.5">
                        <p class="text-sm font-bold text-slate-700">Religion</p>
                        <span class="text-slate-600">{{ $personalInfo->religion ?? '—' }}</span>
                    </div>

                    <div class="rounded-lg border border-slate-200 px-4 py-2.5">
                        <p class="text-sm font-bold text-slate-700">Disability (if any)</p>
                        <span class="text-slate-600">{{ $personalInfo->disability ?? '—' }}</span>
                    </div>

                    <div class="rounded-lg border border-slate-200 px-4 py-2.5">
                        <p class="text-sm font-bold text-slate-700">Ethnic Group</p>
                        <span class="text-slate-600">{{ $personalInfo->ethnic_group ?? '—' }}</span>
                    </div>
                </div>

                <p class="mt-6 text-sm text-slate-500">
                    This information can no longer be edited since your application has already been evaluated.
                </p>
            @else
                <p class="text-slate-500">
                    You haven't submitted an application yet. Personal information will appear here once you apply for a position.
                </p>
            @endif
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
