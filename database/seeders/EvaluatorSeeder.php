<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EvaluatorSeeder extends Seeder
{
    /**
     * Snapshot of the real evaluator profile records currently in the
     * system.
     */
    public function run(): void
    {
        DB::table('evaluators')->insert(
            [
            [
                'id' => 1,
                'name' => 'Juan Dela Cruz',
                'email' => 'evaluator@deped.gov.ph',
                'status' => 'approved',
                'created_at' => '2026-07-22 07:53:42',
                'updated_at' => '2026-07-22 07:53:42',
            ],
        ]
        );
    }
}
