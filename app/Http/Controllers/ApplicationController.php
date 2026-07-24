<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\JobPosition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class ApplicationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | SHOW AVAILABLE JOB POSITIONS
    |--------------------------------------------------------------------------
    */

    public function jobs(): View
    {
        $jobs = JobPosition::query()
            ->where('is_open', true)
            ->latest()
            ->get();

        return view('jobs', compact('jobs'));
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW APPLICATION FORM
    |--------------------------------------------------------------------------
    */

    public function create(JobPosition $job): View
    {
        if (! $job->is_open) {
            abort(403, 'This job position is currently closed.');
        }

        return view('apply', compact('job'));
    }

    /*
    |--------------------------------------------------------------------------
    | STORE APPLICATION
    |--------------------------------------------------------------------------
    */

    public function store(Request $request, JobPosition $job): RedirectResponse
    {
        if (! $job->is_open) {
            abort(403, 'This job position is currently closed.');
        }

        $validated = $request->validate([
            /*
            |--------------------------------------------------------------------------
            | Personal Information
            |--------------------------------------------------------------------------
            */

            'full_name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
            ],

            'phone_number' => [
                'nullable',
                'string',
                'max:50',
            ],

            'address' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'birth_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],

            'sex' => [
                'nullable',
                'string',
                'max:30',
            ],

            'civil_status' => [
                'nullable',
                'string',
                'max:50',
            ],

            'religion' => [
                'nullable',
                'string',
                'max:255',
            ],

            'disability' => [
                'nullable',
                'string',
                'max:255',
            ],

            'ethnic_group' => [
                'nullable',
                'string',
                'max:255',
            ],

            /*
            |--------------------------------------------------------------------------
            | Education
            |--------------------------------------------------------------------------
            */

            'education' => [
                'nullable',
                'array',
            ],

            'education.*.level' => [
                'nullable',
                'string',
                'max:255',
            ],

            'education.*.school' => [
                'nullable',
                'string',
                'max:255',
            ],

            'education.*.degree' => [
                'nullable',
                'string',
                'max:255',
            ],

            'education.*.year_graduated' => [
                'nullable',
                'string',
                'max:20',
            ],

            /*
            |--------------------------------------------------------------------------
            | Work Experience
            |--------------------------------------------------------------------------
            */

            'experience' => [
                'nullable',
                'array',
            ],

            'experience.*.title' => [
                'nullable',
                'string',
                'max:255',
            ],

            'experience.*.company' => [
                'nullable',
                'string',
                'max:255',
            ],

            'experience.*.years_months' => [
                'nullable',
                'string',
                'max:100',
            ],

            'experience.*.details' => [
                'nullable',
                'string',
                'max:2000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Trainings
            |--------------------------------------------------------------------------
            */

            'training' => [
                'nullable',
                'array',
            ],

            'training.*.title' => [
                'nullable',
                'string',
                'max:255',
            ],

            'training.*.hours' => [
                'nullable',
                'string',
                'max:50',
            ],

            'training.*.details' => [
                'nullable',
                'string',
                'max:2000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Eligibility
            |--------------------------------------------------------------------------
            */

            'eligibility' => [
                'nullable',
                'array',
            ],

            'eligibility.*.license_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'eligibility.*.rating' => [
                'nullable',
                'string',
                'max:100',
            ],

            'eligibility.*.valid_until' => [
                'nullable',
                'date',
            ],

            'eligibility.*.never_expires' => [
                'nullable',
                'boolean',
            ],

            /*
            |--------------------------------------------------------------------------
            | Documents
            |--------------------------------------------------------------------------
            */

            'letter_of_intent' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:10240',
            ],

            'tor_diploma' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:10240',
            ],

            'prc_license' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:10240',
            ],

            'eligibility_file' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:10240',
            ],

            'training_certificates' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:10240',
            ],

            'employment_records' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:10240',
            ],

            'latest_appointment' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:10240',
            ],

            'performance_rating' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:10240',
            ],

            'cav' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:10240',
            ],

            'movs' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:10240',
            ],
        ]);

        try {
            DB::transaction(function () use ($validated, $request, $job): void {
                $application = Application::create([
                    'job_position_id' => $job->id,
                    'status' => 'pending',
                ]);

                /*
                |--------------------------------------------------------------------------
                | Applicant Profile
                |--------------------------------------------------------------------------
                */

                $application->profile()->create([
                    'full_name' => $validated['full_name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone_number'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'birth_date' => $validated['birth_date'] ?? null,
                    'sex' => $validated['sex'] ?? null,
                    'civil_status' => $validated['civil_status'] ?? null,
                    'religion' => $validated['religion'] ?? null,
                    'disability' => $validated['disability'] ?? null,
                    'ethnic_group' => $validated['ethnic_group'] ?? null,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Education
                |--------------------------------------------------------------------------
                */

                foreach ($validated['education'] ?? [] as $education) {
                    if (! $this->hasEnteredData($education)) {
                        continue;
                    }

                    $application->educations()->create([
                        'level' => $education['level'] ?? null,
                        'school' => $education['school'] ?? null,
                        'degree' => $education['degree'] ?? null,
                        'year_graduated' => $education['year_graduated'] ?? null,
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Experience
                |--------------------------------------------------------------------------
                */

                foreach ($validated['experience'] ?? [] as $experience) {
                    if (! $this->hasEnteredData($experience)) {
                        continue;
                    }

                    $application->experiences()->create([
                        'title' => $experience['title'] ?? null,
                        'company' => $experience['company'] ?? null,
                        'years_months' => $experience['years_months'] ?? null,
                        'details' => $experience['details'] ?? null,
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Trainings
                |--------------------------------------------------------------------------
                */

                foreach ($validated['training'] ?? [] as $training) {
                    if (! $this->hasEnteredData($training)) {
                        continue;
                    }

                    $application->trainings()->create([
                        'title' => $training['title'] ?? null,
                        'hours' => $training['hours'] ?? null,
                        'details' => $training['details'] ?? null,
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Eligibilities
                |--------------------------------------------------------------------------
                */

                foreach ($validated['eligibility'] ?? [] as $eligibility) {
                    if (! $this->hasEnteredData($eligibility)) {
                        continue;
                    }

                    $neverExpires = filter_var(
                        $eligibility['never_expires'] ?? false,
                        FILTER_VALIDATE_BOOLEAN
                    );

                    $application->eligibilities()->create([
                        'license_name' => $eligibility['license_name'] ?? null,
                        'rating' => $eligibility['rating'] ?? null,
                        'valid_until' => $neverExpires
                            ? null
                            : ($eligibility['valid_until'] ?? null),
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Uploaded Documents
                |--------------------------------------------------------------------------
                */

                $documentFields = [
                    'letter_of_intent',
                    'tor_diploma',
                    'prc_license',
                    'eligibility_file',
                    'training_certificates',
                    'employment_records',
                    'latest_appointment',
                    'performance_rating',
                    'cav',
                    'movs',
                ];

                foreach ($documentFields as $field) {
                    if (! $request->hasFile($field)) {
                        continue;
                    }

                    $file = $request->file($field);
                    $path = $file->store('documents', 'public');

                    $application->documents()->create([
                        'type' => $field,
                        'file_path' => $path,
                    ]);
                }
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'The application could not be submitted. Please try again.'
                );
        }

        return redirect()
            ->route('jobs.index')
            ->with(
                'success',
                'Your application was submitted successfully.'
            );
    }

    private function hasEnteredData(array $data): bool
    {
        foreach ($data as $value) {
            if ($value !== null && $value !== '' && $value !== false) {
                return true;
            }
        }

        return false;
    }
}
