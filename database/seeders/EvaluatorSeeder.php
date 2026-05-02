<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Evaluator;

class EvaluatorSeeder extends Seeder
{
    public function run(): void
    {
        $evaluators = [
            [
                'name' => 'Juan Dela Cruz',
                'email' => 'juan@example.com',
                'status' => 'pending',
            ],
            [
                'name' => 'Maria Santos',
                'email' => 'maria@example.com',
                'status' => 'approved',
            ],
            [
                'name' => 'Pedro Reyes',
                'email' => 'pedro@example.com',
                'status' => 'rejected',
            ],
        ];

        foreach ($evaluators as $evaluator) {
            Evaluator::create($evaluator);
        }
    }
}