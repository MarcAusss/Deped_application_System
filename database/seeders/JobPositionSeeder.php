<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JobPosition;

class JobPositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = $positions = [
            [
                'title' => 'Teacher I',
                'is_open' => true,
                'description' => 'Entry-level teaching position for elementary education.'
            ],
            [
                'title' => 'Teacher II',
                'is_open' => true,
                'description' => 'Mid-level teaching position requiring experience.'
            ],
            [
                'title' => 'School Clerk',
                'is_open' => false,
                'description' => 'Administrative support for school operations.'
            ],
            [
                'title' => 'Guidance Counselor',
                'is_open' => true,
                'description' => 'Provides student counseling and support services.'
            ],
            [
                'title' => 'Principal I',
                'is_open' => false,
                'description' => 'School head responsible for overall administration.'
            ],
        ];

        foreach ($positions as $position) {
            JobPosition::create($position);
        }
    }
}