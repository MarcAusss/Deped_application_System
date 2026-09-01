<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ApplicantDashboardController extends Controller
{
    public function index(): View
    {
        $applications = Auth::guard('applicant')->user()
            ->applications()
            ->with(['jobPosition', 'evaluation', 'controlNumber'])
            ->latest()
            ->get();

        return view('applicant.dashboard', compact('applications'));
    }
}
