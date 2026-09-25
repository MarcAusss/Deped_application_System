<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Notifications\ApplicantPasswordResetCodeNotification;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ApplicantPasswordResetController extends Controller
{
    private const CODE_EXPIRY_MINUTES = 60;

    public function showForgot(): View
    {
        return view('applicant.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $applicant = Applicant::where('email', $validated['email'])->first();

        if (! $applicant) {
            return back()->withInput()->withErrors([
                'email' => 'We could not find an applicant account with that email address.',
            ]);
        }

        $code = (string) random_int(100000, 999999);

        DB::table('applicant_password_reset_tokens')->updateOrInsert(
            ['email' => $applicant->email],
            ['token' => Hash::make($code), 'created_at' => now()]
        );

        $applicant->notify(new ApplicantPasswordResetCodeNotification($code));

        return redirect()
            ->route('applicant.password.reset', ['email' => $applicant->email])
            ->with('status', 'We have emailed a 6-digit verification code to your address.');
    }

    public function showReset(Request $request): View
    {
        return view('applicant.reset-password', [
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $record = DB::table('applicant_password_reset_tokens')
            ->where('email', $validated['email'])
            ->first();

        if (! $record || ! Hash::check($validated['code'], $record->token)) {
            return back()->withInput()->withErrors([
                'code' => 'That code is invalid. Please check it and try again.',
            ]);
        }

        if (Carbon::parse($record->created_at)->addMinutes(self::CODE_EXPIRY_MINUTES)->isPast()) {
            DB::table('applicant_password_reset_tokens')->where('email', $validated['email'])->delete();

            return back()->withInput()->withErrors([
                'code' => 'This code has expired. Please request a new one.',
            ]);
        }

        $applicant = Applicant::where('email', $validated['email'])->first();

        if (! $applicant) {
            return back()->withInput()->withErrors([
                'email' => 'We could not find an applicant account with that email address.',
            ]);
        }

        $applicant->forceFill(['password' => $validated['password']])->save();

        DB::table('applicant_password_reset_tokens')->where('email', $validated['email'])->delete();

        return redirect()->route('applicant.login')->with('status', 'Your password has been reset. Please login.');
    }
}
