<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ApplicationController;
use App\Models\JobPosition;

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');

/*
|--------------------------------------------------------------------------
| PUBLIC JOB LISTING (FOR APPLICANTS)
|--------------------------------------------------------------------------
*/

Route::get('/jobs', [ApplicationController::class, 'jobs'])->name('jobs');

/*
|--------------------------------------------------------------------------
| APPLICATION FORM
|--------------------------------------------------------------------------
*/

Route::get('/apply/{job}', [ApplicationController::class, 'create'])
    ->name('apply.form');

Route::post('/apply/{job}', [ApplicationController::class, 'store'])
    ->name('apply.submit');