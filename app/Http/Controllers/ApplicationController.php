<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\JobPosition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApplicationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | SHOW JOB LIST
    |--------------------------------------------------------------------------
    */

    public function jobs()
    {
        $jobs = JobPosition::where('is_open', true)->latest()->get();

        return view('jobs', compact('jobs'));
    }
    /*
    |--------------------------------------------------------------------------
    | SHOW APPLICATION FORM
    |--------------------------------------------------------------------------
    */

    public function create(JobPosition $job)
    {
        if (!$job->is_open) {
            abort(403, 'Job is not open');
        }

        return view('apply', compact('job'));
    }

    /*
    |--------------------------------------------------------------------------
    | STORE APPLICATION
    |--------------------------------------------------------------------------
    */

    public function store(Request $request, JobPosition $job)
    {
        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            // PERSONAL INFO
            'full_name' => 'required|string',
            'email' => 'required|email',
            'phone_number' => 'nullable|string',
            'address' => 'nullable|string',
            'disability' => 'nullable|string',
            'ethnic_group' => 'nullable|string',

            // ARRAYS
            'education' => 'nullable|array',
            'experience' => 'nullable|array',
            'training' => 'nullable|array',
            'eligibility' => 'nullable|array',

            // DOCUMENTS
            'letter_of_intent' => 'nullable|file|mimes:pdf|max:10240',
            'tor_diploma' => 'nullable|file|mimes:pdf|max:10240',
            'prc_license' => 'nullable|file|mimes:pdf|max:10240',

            // 🔥 RENAMED
            'eligibility_file' => 'nullable|file|mimes:pdf|max:10240',

            'training_certificates' => 'nullable|file|mimes:pdf|max:10240',
            'employment_records' => 'nullable|file|mimes:pdf|max:10240',
            'latest_appointment' => 'nullable|file|mimes:pdf|max:10240',
            'performance_rating' => 'nullable|file|mimes:pdf|max:10240',
            'cav' => 'nullable|file|mimes:pdf|max:10240',
            'movs' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        DB::transaction(function () use ($validated, $request, $job) {

            /*
            |--------------------------------------------------------------------------
            | CREATE APPLICATION
            |--------------------------------------------------------------------------
            */

            $application = Application::create([
                'job_position_id' => $job->id,
                'status' => 'pending',
            ]);

            /*
            |--------------------------------------------------------------------------
            | PROFILE
            |--------------------------------------------------------------------------
            */

            $application->profile()->create([
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone_number'] ?? null,
                'address' => $validated['address'] ?? null,
                'disability' => $validated['disability'] ?? null,
                'ethnic_group' => $validated['ethnic_group'] ?? null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | EDUCATION
            |--------------------------------------------------------------------------
            */

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

            /*
            |--------------------------------------------------------------------------
            | EXPERIENCE
            |--------------------------------------------------------------------------
            */

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

            /*
            |--------------------------------------------------------------------------
            | TRAININGS
            |--------------------------------------------------------------------------
            */

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

            /*
            |--------------------------------------------------------------------------
            | ELIGIBILITIES
            |--------------------------------------------------------------------------
            */

            if (!empty($request->eligibility)) {

                foreach ($request->eligibility as $eligibility) {

                    if (!empty(array_filter($eligibility))) {

                        $application->eligibilities()->create([
                            'license_name' => $eligibility['license_name'] ?? null,
                            'rating' => $eligibility['rating'] ?? null,

                            'valid_until' =>
                                ($eligibility['never_expires'] ?? false)
                                ? null
                                : ($eligibility['valid_until'] ?? null),
                        ]);
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | DOCUMENT UPLOADS
            |--------------------------------------------------------------------------
            */

            $documentFields = [
                'letter_of_intent',
                'tor_diploma',
                'prc_license',

                // 🔥 RENAMED
                'eligibility_file',

                'training_certificates',
                'employment_records',
                'latest_appointment',
                'performance_rating',
                'cav',
                'movs',
            ];

            foreach ($documentFields as $field) {

                if ($request->hasFile($field)) {

                    $file = $request->file($field);

                    $path = $file->store('documents', 'public');

                    $application->documents()->create([
                        'type' => $field,
                        'file_path' => $path,
                    ]);
                }
            }
        });

        return back()->with('success', 'Application submitted successfully!');
    }
}
