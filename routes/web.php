<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ApplicationController;







Route::get('/', [AuthController::class, 'showLogin'])
    ->name('login');

Route::post('/login', [AuthController::class, 'login'])
    ->name('login.submit');

Route::get('/logout', [AuthController::class, 'logout'])
    ->name('logout');







Route::get('/jobs', [ApplicationController::class, 'jobs'])
    ->name('jobs');







Route::get('/apply/{job}', [ApplicationController::class, 'create'])
    ->name('apply.form');

Route::post('/apply/{job}', [ApplicationController::class, 'store'])
    ->name('apply.submit');


Route::get('/jobs', [ApplicationController::class, 'jobs'])
    ->name('jobs.index');

Route::get('/apply/{job}', [ApplicationController::class, 'create'])
    ->name('apply.form');

Route::post('/apply/{job}', [ApplicationController::class, 'store'])
    ->name('apply.submit');