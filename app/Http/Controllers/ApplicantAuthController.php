<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ApplicantAuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const LOCKOUT_SECONDS = 60;

    public function showRegister(): View|RedirectResponse
    {
        if (Auth::guard('applicant')->check()) {
            return redirect()->route('applicant.dashboard');
        }

        return view('applicant.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:applicants,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $applicant = Applicant::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        Auth::guard('applicant')->login($applicant);

        $request->session()->regenerate();

        return redirect()->intended(route('applicant.dashboard'));
    }

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('applicant')->check()) {
            return redirect()->route('applicant.dashboard');
        }

        return view('applicant.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()
                ->withInput()
                ->withErrors(['email' => "Too many failed login attempts. Please try again in {$seconds} second(s)."])
                ->with('lockoutSeconds', $seconds);
        }

        if (! Auth::guard('applicant')->attempt($credentials)) {
            // Once an account has already been locked out at least once, any further
            // wrong password immediately re-locks it (no more free attempts), and each
            // re-lock extends the lockout duration by another minute.
            if ($this->lockoutLevel($request) > 0) {
                $seconds = $this->escalateLockout($request, $throttleKey);

                return back()
                    ->withInput()
                    ->withErrors(['email' => "Invalid email or password. Your account has been locked again for {$seconds} second(s)."])
                    ->with('lockoutSeconds', $seconds);
            }

            RateLimiter::hit($throttleKey, self::LOCKOUT_SECONDS);

            if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
                $this->bumpLockoutLevel($request);

                $seconds = RateLimiter::availableIn($throttleKey);

                return back()
                    ->withInput()
                    ->withErrors(['email' => "Too many failed login attempts. Please try again in {$seconds} second(s)."])
                    ->with('lockoutSeconds', $seconds);
            }

            $remaining = RateLimiter::remaining($throttleKey, self::MAX_ATTEMPTS);

            return back()
                ->withInput()
                ->withErrors(['email' => "Invalid email or password. {$remaining} attempt(s) remaining before your account is temporarily locked."])
                ->with('loginWarning', $remaining <= 2);
        }

        RateLimiter::clear($throttleKey);
        $this->clearLockoutLevel($request);

        $request->session()->regenerate();

        return redirect()->intended(route('applicant.dashboard'));
    }

    private function throttleKey(Request $request): string
    {
        return Str::lower($request->input('email')) . '|' . $request->ip();
    }

    private function lockoutLevelKey(Request $request): string
    {
        return 'login-lockout-level:' . $this->throttleKey($request);
    }

    private function lockoutLevel(Request $request): int
    {
        return (int) Cache::get($this->lockoutLevelKey($request), 0);
    }

    private function bumpLockoutLevel(Request $request): int
    {
        $level = $this->lockoutLevel($request) + 1;

        Cache::put($this->lockoutLevelKey($request), $level, now()->addDay());

        return $level;
    }

    private function clearLockoutLevel(Request $request): void
    {
        Cache::forget($this->lockoutLevelKey($request));
    }

    /**
     * Re-lock the account for another (level * base lockout) seconds and
     * return the new lockout duration.
     */
    private function escalateLockout(Request $request, string $throttleKey): int
    {
        $level = $this->bumpLockoutLevel($request);
        $seconds = self::LOCKOUT_SECONDS * $level;

        RateLimiter::clear($throttleKey);

        for ($i = 0; $i < self::MAX_ATTEMPTS; $i++) {
            RateLimiter::hit($throttleKey, $seconds);
        }

        return $seconds;
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('applicant')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('jobs.index');
    }
}
