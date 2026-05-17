<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | SHOW LOGIN PAGE
    |--------------------------------------------------------------------------
    */

    public function showLogin()
    {
        // Already logged in
        if (auth()->check()) {

            $user = auth()->user();

            // ADMIN
            if ($user->role === 'admin') {
                return redirect('/admin');
            }

            // EVALUATOR
            if ($user->role === 'evaluator') {
                return redirect('/evaluator');
            }
        }

        return view('auth.login');
    }

    /*
    |--------------------------------------------------------------------------
    | LOGIN
    |--------------------------------------------------------------------------
    */

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {

            $request->session()->regenerate();

            $user = Auth::user();

            // ADMIN
            if ($user->role === 'admin') {
                return redirect('/admin');
            }

            // EVALUATOR
            if ($user->role === 'evaluator') {
                return redirect('/evaluator');
            }

            // INVALID ROLE
            Auth::logout();

            return back()->withErrors([
                'email' => 'Unauthorized role.',
            ]);
        }

        return back()->withErrors([
            'email' => 'Invalid email or password.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}