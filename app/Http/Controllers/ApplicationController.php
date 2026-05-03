<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\JobPosition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApplicationController extends Controller
{
    public function create(JobPosition $job)
    {
        if (!$job->is_open) {
            abort(403, 'Job is not open');
        }

        return view('apply', compact('job'));
    }

    public function store(Request $request, JobPosition $job)
    {
        // 🔒 VALIDATION (basic but clean)
        $validated = $request->validate([
            'full_name' => 'required|string',
            'email' => 'required|email',
            'phone_number' => 'nullable|string',
            'address' => 'nullable|string',
            'disability' => 'nullable|string',
            'ethnic_group' => 'nullable|string',

            'education' => 'nullable|array',
            'experience' => 'nullable|array',
            'training' => 'nullable|array',
        ]);

        DB::transaction(function () use ($validated, $request, $job) {

            // 🧾 1. CREATE MAIN APPLICATION
            $application = Application::create([
                'job_position_id' => $job->id,
                'status' => 'pending',
            ]);

            // 👤 2. PROFILE (1:1)
            $application->profile()->create([
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone_number'] ?? null,
                'address' => $validated['address'] ?? null,
                'disability' => $validated['disability'] ?? null,
                'ethnic_group' => $validated['ethnic_group'] ?? null,
            ]);

            // 🎓 3. EDUCATION (1:N CLEAN FIX)
            if (!empty($request->education)) {
                foreach ($request->education as $edu) {
                    if (!empty(array_filter($edu))) {
                        $application->educations()->create([
                            'level' => $edu['level'] ?? null,
                            'school' => $edu['school'] ?? null,
                            'degree' => $edu['degree'] ?? null,
                            'year_graduated' => $edu['year_graduated'] ?? null,
                        ]);
                    }
                }
            }

            // 💼 4. EXPERIENCE
            if (!empty($request->experience)) {
                foreach ($request->experience as $exp) {
                    if (!empty(array_filter($exp))) {
                        $application->experiences()->create([
                            'title' => $exp['title'] ?? null,
                            'company' => $exp['company'] ?? null,
                            'years_months' => $exp['years_months'] ?? null,
                            'details' => $exp['details'] ?? null,
                        ]);
                    }
                }
            }

            // 🏫 5. TRAINING
            if (!empty($request->training)) {
                foreach ($request->training as $train) {
                    if (!empty(array_filter($train))) {
                        $application->trainings()->create([
                            'title' => $train['title'] ?? null,
                            'hours' => $train['hours'] ?? null,
                            'details' => $train['details'] ?? null,
                        ]);
                    }
                }
            }

            // 📁 6. DOCUMENTS (optional placeholder for now)
            // You will connect file upload UI later
        });

        return back()->with('success', 'Application submitted successfully!');
    }
}