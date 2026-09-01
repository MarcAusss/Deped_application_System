<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationControlNumber;
use App\Models\JobPosition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControlNumberGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_generated_control_number_matches_the_expected_format(): void
    {
        $job = JobPosition::create([
            'title' => 'Teacher I',
            'description' => 'Test',
            'is_open' => true,
        ]);

        $controlNumber = ApplicationControlNumber::generateFor($job);

        $this->assertMatchesRegularExpression(
            '/^Alb-Teacher I-\d{4}-' . now()->year . '$/',
            $controlNumber
        );
    }

    public function test_generated_control_numbers_are_unique_across_applications(): void
    {
        $evaluator = User::create([
            'name' => 'Test Evaluator',
            'email' => 'evaluator2@example.com',
            'password' => 'password123',
            'role' => 'evaluator',
            'is_approved' => true,
        ]);

        $job = JobPosition::create([
            'title' => 'Admin Aide I',
            'description' => 'Test',
            'is_open' => true,
        ]);

        $numbers = [];

        for ($i = 0; $i < 20; $i++) {
            $app = Application::create(['job_position_id' => $job->id, 'status' => 'pending']);
            $cn = ApplicationControlNumber::generateFor($job);
            ApplicationControlNumber::create([
                'application_id' => $app->id,
                'control_number' => $cn,
                'generated_by' => $evaluator->id,
            ]);
            $numbers[] = $cn;
        }

        $this->assertCount(20, array_unique($numbers));
    }

    public function test_assigning_a_control_number_does_not_change_application_status(): void
    {
        $evaluator = User::create([
            'name' => 'Test Evaluator',
            'email' => 'evaluator3@example.com',
            'password' => 'password123',
            'role' => 'evaluator',
            'is_approved' => true,
        ]);

        $job = JobPosition::create([
            'title' => 'Admin Aide I',
            'description' => 'Test',
            'is_open' => true,
        ]);

        $application = Application::create(['job_position_id' => $job->id, 'status' => 'pending']);

        ApplicationControlNumber::create([
            'application_id' => $application->id,
            'control_number' => ApplicationControlNumber::generateFor($job),
            'generated_by' => $evaluator->id,
        ]);

        $this->assertSame('pending', $application->fresh()->status);
    }
}
