<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ApplicantProfileController extends Controller
{
    public function edit(): View
    {
        $applicant = Auth::guard('applicant')->user();

        $latestApplication = $applicant->applications()
            ->with('profile')
            ->latest()
            ->first();

        return view('applicant.profile', [
            'applicant' => $applicant,
            'personalInfoApplication' => $latestApplication,
            'personalInfo' => $latestApplication?->profile,
        ]);
    }

    public function updatePersonalInfo(Request $request): RedirectResponse
    {
        $applicant = Auth::guard('applicant')->user();

        $application = $applicant->applications()
            ->with('profile')
            ->latest()
            ->first();

        if (! $application || $application->status !== 'pending') {
            return back()->with('error', 'Personal information can no longer be edited since your application has already been evaluated.');
        }

        $validated = $request->validateWithBag('updatePersonalInfo', [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'sex' => ['nullable', 'string', 'max:30'],
            'civil_status' => ['nullable', 'string', 'max:50'],
            'religion' => ['nullable', 'string', 'max:255'],
            'disability' => ['nullable', 'string', 'max:255'],
            'ethnic_group' => ['nullable', 'string', 'max:255'],
        ]);

        $application->profile()->update([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone_number'] ?? null,
            'address' => $validated['address'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'sex' => $validated['sex'] ?? null,
            'civil_status' => $validated['civil_status'] ?? null,
            'religion' => $validated['religion'] ?? null,
            'disability' => $validated['disability'] ?? null,
            'ethnic_group' => $validated['ethnic_group'] ?? null,
        ]);

        return back()->with('success', 'Personal information updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $applicant = Auth::guard('applicant')->user();

        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password:applicant'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $applicant->update(['password' => $validated['password']]);

        return back()->with('success', 'Password changed successfully.');
    }
}
