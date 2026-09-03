<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ApplicantPasswordResetController extends Controller
{
    public function showForgot(): View
    {
        return view('applicant.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::broker('applicants')->sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', 'We have emailed your password reset link.')
            : back()->withInput()->withErrors(['email' => __($status)]);
    }

    public function showReset(Request $request, string $token): View
    {
        return view('applicant.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::broker('applicants')->reset(
            $validated,
            function ($applicant, $password) {
                $applicant->forceFill(['password' => $password])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('applicant.login')->with('status', 'Your password has been reset. Please login.')
            : back()->withInput()->withErrors(['email' => __($status)]);
    }
}
