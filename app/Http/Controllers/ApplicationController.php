<?php

namespace App\Http\Controllers;

use App\Mail\ApplicationSubmitted;
use App\Models\Application;
use App\Models\ApplicationControlNumber;
use App\Models\JobPosition;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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

        return view('apply', ['job' => $job, 'application' => null]);
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

        $validated = $request->validate($this->applicationValidationRules());

        try {
            $application = DB::transaction(function () use ($validated, $request, $job): Application {
                $application = Application::create([
                    'job_position_id' => $job->id,
                    'applicant_id' => Auth::guard('applicant')->id(),
                    'status' => 'pending',
                ]);

                $application->controlNumber()->create([
                    'control_number' => ApplicationControlNumber::generateFor($job),
                ]);

                $application->profile()->create($this->profileData($validated));

                $this->syncEducations($application, $validated);
                $this->syncExperiences($application, $validated);
                $this->syncTrainings($application, $validated);
                $this->syncEligibilities($application, $validated);
                $this->storeUploadedDocuments($application, $request);

                return $application;
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

        try {
            $application->load(['jobPosition', 'profile']);

            Mail::to($application->profile->email)
                ->send(new ApplicationSubmitted($application));
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()
            ->route('jobs.index')
            ->with(
                'success',
                'Your application was submitted successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT APPLICATION (applicant, while still Pending)
    |--------------------------------------------------------------------------
    */

    public function edit(Application $application): View|RedirectResponse
    {
        if ($application->applicant_id !== Auth::guard('applicant')->id()) {
            abort(404);
        }

        if ($application->status !== 'pending') {
            return redirect()
                ->route('applicant.dashboard')
                ->with('error', 'This application can no longer be edited since it has already been evaluated.');
        }

        $application->load([
            'jobPosition',
            'profile',
            'educations',
            'experiences',
            'trainings',
            'eligibilities',
            'documents',
        ]);

        return view('apply', [
            'job' => $application->jobPosition,
            'application' => $application,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE APPLICATION (applicant, while still Pending)
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, Application $application): RedirectResponse
    {
        if ($application->applicant_id !== Auth::guard('applicant')->id()) {
            abort(404);
        }

        if ($application->status !== 'pending') {
            return redirect()
                ->route('applicant.dashboard')
                ->with('error', 'This application can no longer be edited since it has already been evaluated.');
        }

        $validated = $request->validate($this->applicationValidationRules());

        try {
            DB::transaction(function () use ($validated, $request, $application): void {
                $application->profile()->update($this->profileData($validated));

                $application->educations()->delete();
                $application->experiences()->delete();
                $application->trainings()->delete();
                $application->eligibilities()->delete();

                $this->syncEducations($application, $validated);
                $this->syncExperiences($application, $validated);
                $this->syncTrainings($application, $validated);
                $this->syncEligibilities($application, $validated);
                $this->replaceUploadedDocuments($application, $request);
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'The application could not be updated. Please try again.'
                );
        }

        return redirect()
            ->route('applicant.dashboard')
            ->with(
                'success',
                'Your application was updated successfully.'
            );
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function applicationValidationRules(): array
    {
        return [
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

            'education.*.level_specify' => [
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

            'experience.*.first_day' => [
                'nullable',
                'string',
                'max:100',
            ],

            'experience.*.last_day' => [
                'nullable',
                'string',
                'max:100',
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
                'integer',
                'min:0',
            ],

            'training.*.training_date' => [
                'nullable',
                'date_format:Y-m',
                'before_or_equal:'.now()->format('Y-m'),
            ],

            'training.*.training_end_date' => [
                'nullable',
                'date_format:Y-m',
                'before_or_equal:'.now()->format('Y-m'),
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

            'eligibility.*.license_specify' => [
                'nullable',
                'string',
                'max:255',
            ],

            'eligibility.*.rating' => [
                'nullable',
                'string',
                'max:100',
            ],

            'eligibility.*.date_issued' => [
                'nullable',
                'date',
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
        ];
    }

    /**
     * @return array<int, string>
     */
    private function documentFields(): array
    {
        return [
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
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function profileData(array $validated): array
    {
        return [
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
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncEducations(Application $application, array $validated): void
    {
        foreach ($validated['education'] ?? [] as $education) {
            if (! $this->hasEnteredData($education)) {
                continue;
            }

            $application->educations()->create([
                'level' => $education['level'] ?? null,
                'level_specify' => ($education['level'] ?? null) === "Other's"
                    ? ($education['level_specify'] ?? null)
                    : null,
                'school' => $education['school'] ?? null,
                'degree' => $education['degree'] ?? null,
                'year_graduated' => $education['year_graduated'] ?? null,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncExperiences(Application $application, array $validated): void
    {
        foreach ($validated['experience'] ?? [] as $experience) {
            if (! $this->hasEnteredData($experience)) {
                continue;
            }

            $application->experiences()->create([
                'title' => $experience['title'] ?? null,
                'company' => $experience['company'] ?? null,
                'first_day' => $experience['first_day'] ?? null,
                'last_day' => $experience['last_day'] ?? null,
                'years_months' => $experience['years_months'] ?? null,
                'details' => $experience['details'] ?? null,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncTrainings(Application $application, array $validated): void
    {
        foreach ($validated['training'] ?? [] as $training) {
            if (! $this->hasEnteredData($training)) {
                continue;
            }

            $application->trainings()->create([
                'title' => $training['title'] ?? null,
                'hours' => $training['hours'] ?? null,
                'training_date' => filled($training['training_date'] ?? null)
                    ? Carbon::createFromFormat('!Y-m', $training['training_date'])->toDateString()
                    : null,
                'training_end_date' => filled($training['training_end_date'] ?? null)
                    ? Carbon::createFromFormat('!Y-m', $training['training_end_date'])->toDateString()
                    : null,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncEligibilities(Application $application, array $validated): void
    {
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
                'license_specify' => in_array($eligibility['license_name'] ?? null, ['RA1080', "Other's"], true)
                    ? ($eligibility['license_specify'] ?? null)
                    : null,
                'rating' => $eligibility['rating'] ?? null,
                'date_issued' => $eligibility['date_issued'] ?? null,
                'valid_until' => $neverExpires
                    ? null
                    : ($eligibility['valid_until'] ?? null),
                'never_expires' => $neverExpires,
            ]);
        }
    }

    private function storeUploadedDocuments(Application $application, Request $request): void
    {
        foreach ($this->documentFields() as $field) {
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
    }

    private function replaceUploadedDocuments(Application $application, Request $request): void
    {
        foreach ($this->documentFields() as $field) {
            if (! $request->hasFile($field)) {
                continue;
            }

            $existing = $application->documents()->where('type', $field)->first();

            if ($existing) {
                Storage::disk('public')->delete($existing->file_path);
                $existing->delete();
            }

            $file = $request->file($field);
            $path = $file->store('documents', 'public');

            $application->documents()->create([
                'type' => $field,
                'file_path' => $path,
            ]);
        }
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
