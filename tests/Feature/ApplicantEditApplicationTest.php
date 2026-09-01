<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\JobPosition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicantEditApplicationTest extends TestCase
{
    use RefreshDatabase;

    private function createSubmittedApplication(Applicant $applicant, JobPosition $job): Application
    {
        $this->actingAs($applicant, 'applicant')
            ->post(route('apply.submit', $job), [
                'full_name' => 'Original Name',
                'email' => 'original@example.com',
                'education' => [
                    ['level' => "Bachelor's Degree", 'school' => 'Original School', 'degree' => 'BS Original', 'year_graduated' => '2020'],
                ],
            ]);

        return Application::where('applicant_id', $applicant->id)->firstOrFail();
    }

    public function test_edit_page_prefills_submitted_data_for_pending_application(): void
    {
        $applicant = Applicant::create(['name' => 'Test Applicant', 'email' => 'applicant@example.com', 'password' => 'password123']);
        $job = JobPosition::create(['title' => 'Test Position', 'description' => 'Test', 'is_open' => true]);
        $application = $this->createSubmittedApplication($applicant, $job);

        $response = $this->actingAs($applicant, 'applicant')
            ->get(route('applicant.applications.edit', $application));

        $response->assertOk();
        $response->assertSee('Original Name');
        $response->assertSee('original@example.com');
        $response->assertSee('Original School');
        $response->assertSee('Save Changes');
    }

    public function test_update_replaces_profile_and_repeater_data(): void
    {
        $applicant = Applicant::create(['name' => 'Test Applicant', 'email' => 'applicant@example.com', 'password' => 'password123']);
        $job = JobPosition::create(['title' => 'Test Position', 'description' => 'Test', 'is_open' => true]);
        $application = $this->createSubmittedApplication($applicant, $job);

        $response = $this->actingAs($applicant, 'applicant')
            ->put(route('applicant.applications.update', $application), [
                'full_name' => 'Updated Name',
                'email' => 'updated@example.com',
                'education' => [
                    ['level' => "Bachelor's Degree", 'school' => 'Updated School', 'degree' => 'BS Updated', 'year_graduated' => '2021'],
                    ['level' => "Master's Degree", 'school' => 'New Grad School', 'degree' => 'MS New', 'year_graduated' => '2023'],
                ],
            ]);

        $response->assertRedirect(route('applicant.dashboard'));

        $application->refresh();
        $this->assertSame('Updated Name', $application->profile->full_name);
        $this->assertSame('updated@example.com', $application->profile->email);
        $this->assertCount(2, $application->educations);
        $this->assertDatabaseHas('applicant_educations', ['application_id' => $application->id, 'school' => 'Updated School']);
        $this->assertDatabaseMissing('applicant_educations', ['application_id' => $application->id, 'school' => 'Original School']);
    }

    public function test_another_applicant_cannot_edit_someone_elses_application(): void
    {
        $owner = Applicant::create(['name' => 'Owner', 'email' => 'owner@example.com', 'password' => 'password123']);
        $intruder = Applicant::create(['name' => 'Intruder', 'email' => 'intruder@example.com', 'password' => 'password123']);
        $job = JobPosition::create(['title' => 'Test Position', 'description' => 'Test', 'is_open' => true]);
        $application = $this->createSubmittedApplication($owner, $job);

        $this->actingAs($intruder, 'applicant')
            ->get(route('applicant.applications.edit', $application))
            ->assertNotFound();
    }

    public function test_application_cannot_be_edited_once_no_longer_pending(): void
    {
        $applicant = Applicant::create(['name' => 'Test Applicant', 'email' => 'applicant@example.com', 'password' => 'password123']);
        $job = JobPosition::create(['title' => 'Test Position', 'description' => 'Test', 'is_open' => true]);
        $application = $this->createSubmittedApplication($applicant, $job);
        $application->update(['status' => 'evaluated']);

        $response = $this->actingAs($applicant, 'applicant')
            ->get(route('applicant.applications.edit', $application));

        $response->assertRedirect(route('applicant.dashboard'));
        $response->assertSessionHas('error');
    }
}
