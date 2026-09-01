<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\JobPosition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicantAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_applicant_login_when_visiting_apply_form(): void
    {
        $job = JobPosition::create(['title' => 'Test Position', 'description' => 'Test', 'is_open' => true]);

        $this->get(route('apply.form', $job))
            ->assertRedirect(route('applicant.login'));
    }

    public function test_guest_is_redirected_to_applicant_login_when_visiting_dashboard(): void
    {
        $this->get(route('applicant.dashboard'))
            ->assertRedirect(route('applicant.login'));
    }

    public function test_applicant_can_register_and_is_logged_in(): void
    {
        $response = $this->post(route('applicant.register.submit'), [
            'name' => 'Test Applicant',
            'email' => 'applicant@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('applicant.dashboard'));

        $this->assertAuthenticated('applicant');
        $this->assertDatabaseHas('applicants', ['email' => 'applicant@example.com']);
    }

    public function test_applicant_can_login(): void
    {
        $applicant = Applicant::create([
            'name' => 'Test Applicant',
            'email' => 'applicant@example.com',
            'password' => 'password123',
        ]);

        $response = $this->post(route('applicant.login.submit'), [
            'email' => 'applicant@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('applicant.dashboard'));
        $this->assertAuthenticatedAs($applicant, 'applicant');
    }

    public function test_authenticated_applicant_can_submit_an_application_linked_to_their_account(): void
    {
        $applicant = Applicant::create([
            'name' => 'Test Applicant',
            'email' => 'applicant@example.com',
            'password' => 'password123',
        ]);

        $job = JobPosition::create(['title' => 'Test Position', 'description' => 'Test', 'is_open' => true]);

        $response = $this->actingAs($applicant, 'applicant')
            ->post(route('apply.submit', $job), [
                'full_name' => 'Test Applicant',
                'email' => 'applicant@example.com',
            ]);

        $response->assertRedirect(route('jobs.index'));

        $this->assertDatabaseHas('applications', [
            'job_position_id' => $job->id,
            'applicant_id' => $applicant->id,
        ]);

        $application = \App\Models\Application::where('applicant_id', $applicant->id)->firstOrFail();

        $this->assertNotNull($application->controlNumber);
        $this->assertMatchesRegularExpression(
            '/^Alb-Test Position-\d{4}-' . now()->year . '$/',
            $application->controlNumber->control_number
        );
    }
}
