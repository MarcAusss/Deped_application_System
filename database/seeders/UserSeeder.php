<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Snapshot of the real users currently in the system.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'id' => 1,
                'name' => 'Legacy Admin',
                'email' => 'admin@system.com',
                'password' => '$2y$12$SHxvxogommfUja681K5GVuCIfPYJWObyrU1CSlIb2d8RXonzFeiie',
                'role' => 'admin',
                'is_approved' => 1,
                'created_at' => '2026-07-22 07:53:41',
                'updated_at' => '2026-08-20 13:02:46',
            ],
            [
                'id' => 2,
                'name' => 'System Administrator',
                'email' => 'admin@deped.gov.ph',
                'password' => '$2y$12$hlihZH2/DdhTUooSmYZxmu6O/ZgXa0cc0PXwg1T84iXyis3u3zr8i',
                'role' => 'admin',
                'is_approved' => 1,
                'created_at' => '2026-07-22 07:53:42',
                'updated_at' => '2026-08-20 12:48:12',
            ],
            [
                'id' => 3,
                'name' => 'Juan Dela Cruz',
                'email' => 'evaluator@deped.gov.ph',
                'password' => '$2y$12$Qc51Wh0RpBLSze1UQN/td.eP5GLPjpoSKgqv2Yg/P32sm0Oj3IOOC',
                'role' => 'evaluator',
                'is_approved' => 1,
                'created_at' => '2026-07-22 07:53:42',
                'updated_at' => '2026-08-26 15:17:52',
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
        ]);

    }
}
