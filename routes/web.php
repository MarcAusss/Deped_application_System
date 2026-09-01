<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ApplicantAuthController;
use App\Http\Controllers\ApplicantDashboardController;
use App\Http\Controllers\ApplicantProfileController;

Route::get('/', [AuthController::class, 'showLogin'])
    ->name('login');

Route::post('/login', [AuthController::class, 'login'])
    ->name('login.submit');

Route::get('/logout', [AuthController::class, 'logout'])
    ->name('logout');

Route::get('/jobs', [ApplicationController::class, 'jobs'])
    ->name('jobs.index');

Route::get('/applicant/register', [ApplicantAuthController::class, 'showRegister'])
    ->name('applicant.register');

Route::post('/applicant/register', [ApplicantAuthController::class, 'register'])
    ->name('applicant.register.submit');

Route::get('/applicant/login', [ApplicantAuthController::class, 'showLogin'])
    ->name('applicant.login');

Route::post('/applicant/login', [ApplicantAuthController::class, 'login'])
    ->name('applicant.login.submit');

Route::middleware('applicant.auth:applicant')->group(function () {
    Route::post('/applicant/logout', [ApplicantAuthController::class, 'logout'])
        ->name('applicant.logout');

    Route::get('/applicant/dashboard', [ApplicantDashboardController::class, 'index'])
        ->name('applicant.dashboard');

    Route::get('/applicant/profile', [ApplicantProfileController::class, 'edit'])
        ->name('applicant.profile');

    Route::put('/applicant/profile', [ApplicantProfileController::class, 'update'])
        ->name('applicant.profile.update');

    Route::put('/applicant/profile/password', [ApplicantProfileController::class, 'updatePassword'])
        ->name('applicant.profile.password');

    Route::get('/apply/{job}', [ApplicationController::class, 'create'])
        ->name('apply.form');

    Route::post('/apply/{job}', [ApplicationController::class, 'store'])
        ->name('apply.submit');

    Route::get('/applicant/applications/{application}/edit', [ApplicationController::class, 'edit'])
        ->name('applicant.applications.edit');

    Route::put('/applicant/applications/{application}', [ApplicationController::class, 'update'])
        ->name('applicant.applications.update');
});
