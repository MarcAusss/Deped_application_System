<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\JobPosition;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function create($jobId)
    {
        $job = JobPosition::findOrFail($jobId);

        if (!$job->is_open) {
            abort(403, 'Job not available');
        }

        return view('apply', compact('job'));
    }

    public function store(Request $request)
    {
        Application::create($request->all());

        return redirect()->back()->with('success', 'Application submitted!');
    }
}
