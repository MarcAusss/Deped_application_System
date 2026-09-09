<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const LOCKOUT_SECONDS = 60;

    public function showLogin()
    {

        if (auth()->check()) {

            $user = auth()->user();


            if ($user->role === 'admin') {
                return redirect('/admin');
            }


            if ($user->role === 'evaluator') {
                return redirect('/evaluator');
            }
        }

        return view('auth.login');
    }




    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()
                ->withErrors(['email' => "Too many failed login attempts. Please try again in {$seconds} second(s)."])
                ->with('lockoutSeconds', $seconds);
        }

        if (Auth::attempt($credentials)) {

            RateLimiter::clear($throttleKey);
            $this->clearLockoutLevel($request);

            $request->session()->regenerate();

            $user = Auth::user();


            if ($user->role === 'admin') {
                return redirect('/admin');
            }


            if ($user->role === 'evaluator') {
                return redirect('/evaluator');
            }


            Auth::logout();

            return back()->withErrors([
                'email' => 'Unauthorized role.',
            ]);
        }

        // Once an account has already been locked out at least once, any further
        // wrong password immediately re-locks it (no more free attempts), and each
        // re-lock extends the lockout duration by another minute.
        if ($this->lockoutLevel($request) > 0) {
            $seconds = $this->escalateLockout($request, $throttleKey);

            return back()
                ->withErrors(['email' => "Invalid email or password. Your account has been locked again for {$seconds} second(s)."])
                ->with('lockoutSeconds', $seconds);
        }

        RateLimiter::hit($throttleKey, self::LOCKOUT_SECONDS);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $this->bumpLockoutLevel($request);

            $seconds = RateLimiter::availableIn($throttleKey);

            return back()
                ->withErrors(['email' => "Too many failed login attempts. Please try again in {$seconds} second(s)."])
                ->with('lockoutSeconds', $seconds);
        }

        $remaining = RateLimiter::remaining($throttleKey, self::MAX_ATTEMPTS);

        return back()->withErrors([
            'email' => "Invalid email or password. {$remaining} attempt(s) remaining before your account is temporarily locked.",
        ])->with('loginWarning', $remaining <= 2);
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




    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
