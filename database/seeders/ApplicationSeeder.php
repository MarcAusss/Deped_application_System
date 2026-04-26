<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Application;
use App\Models\JobPosition;

class ApplicationSeeder extends Seeder
{
    public function run(): void
    {
        $positions = JobPosition::all();

        if ($positions->isEmpty()) {
            return;
        }

        $statuses = ['pending', 'evaluated', 'approved', 'rejected'];

        for ($i = 1; $i <= 20; $i++) {
            $status = $statuses[array_rand($statuses)];

            Application::create([
                'job_position_id' => $positions->random()->id,
                'full_name' => "Applicant $i",
                'email' => "applicant$i@example.com",
                'phone_number' => '0912345678' . rand(0, 9),
                'resume' => 'Sample resume content',

                'status' => $status,
                'evaluated_by_evaluator' => $status !== 'pending',
                'final_reviewed_by_admin' => in_array($status, ['approved', 'rejected']),
            ]);
        }
    }
}