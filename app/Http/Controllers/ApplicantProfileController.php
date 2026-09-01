<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ApplicantProfileController extends Controller
{
    public function edit(): View
    {
        return view('applicant.profile', [
            'applicant' => Auth::guard('applicant')->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $applicant = Auth::guard('applicant')->user();

        $validated = $request->validateWithBag('updateProfile', [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:applicants,email,' . $applicant->id],
        ]);

        $applicant->update($validated);

        return back()->with('success', 'Profile updated successfully.');
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
