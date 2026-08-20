<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Snapshot of the real users currently in the system, so this exact
     * account list (including hashed passwords) is reproduced on any
     * fresh install.
     */
    public function run(): void
    {
        DB::table('users')->insert(
            [
            [
                'id' => 1,
                'name' => 'Legacy Admin',
                'email' => 'admin@system.com',
                'password' => '$2y$12$qMMqVYTQl1OrsOWz3sCkc.Wi4vClz19HL10vTwrNiTwBGZtUaVadS',
                'role' => 'admin',
                'is_approved' => 1,
                'created_at' => '2026-07-22 07:53:41',
                'updated_at' => '2026-08-20 12:56:57',
            ],
            [
                'id' => 2,
                'name' => 'System Administrator',
                'email' => 'admin@deped.gov.ph',
                'password' => '$2y$12$OJQW3bF9YC2iMss9YUWWfOB1jsX69w9THD24ULRY.bkM332LumEYm',
                'role' => 'admin',
                'is_approved' => 1,
                'created_at' => '2026-07-22 07:53:42',
                'updated_at' => '2026-08-20 09:03:55',
            ],
            [
                'id' => 3,
                'name' => 'Juan Dela Cruz',
                'email' => 'evaluator@deped.gov.ph',
                'password' => '$2y$12$Qc51Wh0RpBLSze1UQN/td.eP5GLPjpoSKgqv2Yg/P32sm0Oj3IOOC',
                'role' => 'evaluator',
                'is_approved' => 1,
                'created_at' => '2026-07-22 07:53:42',
                'updated_at' => '2026-07-22 07:53:42',
            ],
            [
                'id' => 5,
                'name' => 'Cedric Domanais',
                'email' => 'evaluator1@deped.gov.ph',
                'password' => '$2y$12$tGzk7z5X.NreOVnt9pYE9ulVIW5Rzxmo/QKDYI3IF.Sw6WEM0QlNm',
                'role' => 'evaluator',
                'is_approved' => 0,
                'created_at' => '2026-07-22 08:41:15',
                'updated_at' => '2026-08-20 09:00:40',
            ],
        ]
        );
    }
}
