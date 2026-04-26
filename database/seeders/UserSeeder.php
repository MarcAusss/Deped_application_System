<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 👑 ADMIN USER
        User::create([
            'name' => 'System Admin',
            'email' => 'admin@system.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_approved' => true,
        ]);

        // 🧑‍⚖️ EVALUATORS (5 USERS)
        $evaluators = [
            ['name' => 'Evaluator One', 'email' => 'eval1@system.com', 'approved' => true],
            ['name' => 'Evaluator Two', 'email' => 'eval2@system.com', 'approved' => true],
            ['name' => 'Evaluator Three', 'email' => 'eval3@system.com', 'approved' => false],
            ['name' => 'Evaluator Four', 'email' => 'eval4@system.com', 'approved' => true],
            ['name' => 'Evaluator Five', 'email' => 'eval5@system.com', 'approved' => false],
        ];

        foreach ($evaluators as $eval) {
            User::create([
                'name' => $eval['name'],
                'email' => $eval['email'],
                'password' => Hash::make('password123'),
                'role' => 'evaluator',
                'is_approved' => $eval['approved'],
            ]);
        }
    }
}