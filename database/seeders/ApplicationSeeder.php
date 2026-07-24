<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {

            /*
            |--------------------------------------------------------------------------
            | CREATE OR GET ADMIN USER
            |--------------------------------------------------------------------------
            */

            DB::table('users')->updateOrInsert(
                ['email' => 'admin@deped.gov.ph'],
                [
                    'name' => 'System Administrator',
                    'password' => Hash::make('password'),
                    'role' => 'admin',
                    'is_approved' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $adminId = DB::table('users')
                ->where('email', 'admin@deped.gov.ph')
                ->value('id');

            /*
            |--------------------------------------------------------------------------
            | CREATE OR GET APPROVED EVALUATOR USER
            |--------------------------------------------------------------------------
            */

            DB::table('users')->updateOrInsert(
                ['email' => 'evaluator@deped.gov.ph'],
                [
                    'name' => 'Juan Dela Cruz',
                    'password' => Hash::make('password'),
                    'role' => 'evaluator',
                    'is_approved' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $evaluatorId = DB::table('users')
                ->where('email', 'evaluator@deped.gov.ph')
                ->value('id');

            /*
            |--------------------------------------------------------------------------
            | CREATE EVALUATOR PROFILE
            |--------------------------------------------------------------------------
            */

            DB::table('evaluators')->updateOrInsert(
                ['email' => 'evaluator@deped.gov.ph'],
                [
                    'name' => 'Juan Dela Cruz',
                    'status' => 'approved',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | CREATE JOB POSITIONS
            |--------------------------------------------------------------------------
            */

            $jobPositions = [
                [
                    'title' => 'Teacher I',
                    'description' => 'Entry-level teaching position for elementary education.',
                ],
                [
                    'title' => 'Administrative Assistant II',
                    'description' => 'Provides clerical, administrative, and records management support.',
                ],
                [
                    'title' => 'Education Program Specialist II',
                    'description' => 'Assists in planning, implementing, and evaluating education programs.',
                ],
            ];

            foreach ($jobPositions as $position) {
                DB::table('job_positions')->updateOrInsert(
                    ['title' => $position['title']],
                    [
                        'description' => $position['description'],
                        'is_open' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $teacherId = DB::table('job_positions')
                ->where('title', 'Teacher I')
                ->value('id');

            $adminAssistantId = DB::table('job_positions')
                ->where('title', 'Administrative Assistant II')
                ->value('id');

            $educationSpecialistId = DB::table('job_positions')
                ->where('title', 'Education Program Specialist II')
                ->value('id');

            /*
            |--------------------------------------------------------------------------
            | SAMPLE APPLICATION DATA
            |--------------------------------------------------------------------------
            */

            $applications = [
                /*
                |--------------------------------------------------------------------------
                | PENDING / SUBMITTED FOR EVALUATION
                |--------------------------------------------------------------------------
                */

                [
                    'job_position_id' => $teacherId,
                    'status' => 'pending',
                    'full_name' => 'Maria Santos',
                    'email' => 'maria.santos@example.com',
                    'phone' => '09171234501',
                    'address' => 'Legazpi City, Albay',
                    'disability' => null,
                    'ethnic_group' => null,
                    'degree' => 'Bachelor of Elementary Education',
                    'school' => 'Bicol University',
                    'year_graduated' => '2022',
                    'position_title' => 'Substitute Teacher',
                    'company' => 'Legazpi Elementary School',
                    'years_months' => '1 year',
                    'training_title' => 'Classroom Management Seminar',
                    'training_hours' => 16,
                    'license_name' => 'Licensure Examination for Teachers',
                    'rating' => '84.50',
                    'valid_until' => null,
                    'remarks' => 'Submitted and waiting for evaluator review.',
                    'days_ago' => 1,
                ],
                [
                    'job_position_id' => $adminAssistantId,
                    'status' => 'pending',
                    'full_name' => 'Carlo Reyes',
                    'email' => 'carlo.reyes@example.com',
                    'phone' => '09171234502',
                    'address' => 'Daraga, Albay',
                    'disability' => null,
                    'ethnic_group' => null,
                    'degree' => 'Bachelor of Science in Office Administration',
                    'school' => 'Divine Word College of Legazpi',
                    'year_graduated' => '2023',
                    'position_title' => 'Office Clerk',
                    'company' => 'ABC Trading',
                    'years_months' => '8 months',
                    'training_title' => 'Records Management Training',
                    'training_hours' => 8,
                    'license_name' => 'Civil Service Professional',
                    'rating' => '82.10',
                    'valid_until' => null,
                    'remarks' => 'Application submitted for initial evaluation.',
                    'days_ago' => 2,
                ],
                [
                    'job_position_id' => $educationSpecialistId,
                    'status' => 'pending',
                    'full_name' => 'Angela Mendoza',
                    'email' => 'angela.mendoza@example.com',
                    'phone' => '09171234503',
                    'address' => 'Tabaco City, Albay',
                    'disability' => null,
                    'ethnic_group' => null,
                    'degree' => 'Master of Arts in Education',
                    'school' => 'Bicol University Graduate School',
                    'year_graduated' => '2024',
                    'position_title' => 'Project Assistant',
                    'company' => 'Regional Education Office',
                    'years_months' => '2 years',
                    'training_title' => 'Program Monitoring and Evaluation',
                    'training_hours' => 24,
                    'license_name' => 'Licensure Examination for Teachers',
                    'rating' => '86.25',
                    'valid_until' => null,
                    'remarks' => 'Waiting for assignment to an evaluator.',
                    'days_ago' => 3,
                ],

                /*
                |--------------------------------------------------------------------------
                | EVALUATED / FOR ADMIN APPROVAL
                |--------------------------------------------------------------------------
                */

                [
                    'job_position_id' => $teacherId,
                    'status' => 'evaluated',
                    'full_name' => 'Joshua Navarro',
                    'email' => 'joshua.navarro@example.com',
                    'phone' => '09171234504',
                    'address' => 'Ligao City, Albay',
                    'disability' => null,
                    'ethnic_group' => null,
                    'degree' => 'Bachelor of Secondary Education',
                    'school' => 'Bicol University',
                    'year_graduated' => '2021',
                    'position_title' => 'Private School Teacher',
                    'company' => 'Saint Michael Academy',
                    'years_months' => '3 years',
                    'training_title' => 'Teaching Strategies Workshop',
                    'training_hours' => 24,
                    'license_name' => 'Licensure Examination for Teachers',
                    'rating' => '88.20',
                    'valid_until' => null,
                    'remarks' => 'Documents complete. Recommended for admin approval.',
                    'resume_checked' => true,
                    'credentials_valid' => true,
                    'recommended' => true,
                    'days_ago' => 4,
                ],
                [
                    'job_position_id' => $adminAssistantId,
                    'status' => 'evaluated',
                    'full_name' => 'Patricia Gomez',
                    'email' => 'patricia.gomez@example.com',
                    'phone' => '09171234505',
                    'address' => 'Guinobatan, Albay',
                    'disability' => null,
                    'ethnic_group' => null,
                    'degree' => 'Bachelor of Science in Business Administration',
                    'school' => 'University of Santo Tomas-Legazpi',
                    'year_graduated' => '2020',
                    'position_title' => 'Administrative Aide',
                    'company' => 'Municipal Government Office',
                    'years_months' => '4 years',
                    'training_title' => 'Government Records Administration',
                    'training_hours' => 16,
                    'license_name' => 'Civil Service Professional',
                    'rating' => '85.10',
                    'valid_until' => null,
                    'remarks' => 'Qualified and recommended for further processing.',
                    'resume_checked' => true,
                    'credentials_valid' => true,
                    'recommended' => true,
                    'days_ago' => 5,
                ],
                [
                    'job_position_id' => $educationSpecialistId,
                    'status' => 'evaluated',
                    'full_name' => 'Ramon Villanueva',
                    'email' => 'ramon.villanueva@example.com',
                    'phone' => '09171234506',
                    'address' => 'Sorsogon City, Sorsogon',
                    'disability' => null,
                    'ethnic_group' => null,
                    'degree' => 'Master in Public Administration',
                    'school' => 'Bicol University',
                    'year_graduated' => '2019',
                    'position_title' => 'Planning Officer',
                    'company' => 'Local Government Unit',
                    'years_months' => '5 years',
                    'training_title' => 'Project Planning and Development',
                    'training_hours' => 32,
                    'license_name' => 'Civil Service Professional',
                    'rating' => '89.00',
                    'valid_until' => null,
                    'remarks' => 'Evaluation completed. For admin decision.',
                    'resume_checked' => true,
                    'credentials_valid' => true,
                    'recommended' => true,
                    'days_ago' => 6,
                ],

                /*
                |--------------------------------------------------------------------------
                | APPROVED
                |--------------------------------------------------------------------------
                */

                [
                    'job_position_id' => $teacherId,
                    'status' => 'approved',
                    'full_name' => 'Elaine Flores',
                    'email' => 'elaine.flores@example.com',
                    'phone' => '09171234507',
                    'address' => 'Naga City, Camarines Sur',
                    'disability' => null,
                    'ethnic_group' => null,
                    'degree' => 'Bachelor of Elementary Education',
                    'school' => 'Ateneo de Naga University',
                    'year_graduated' => '2018',
                    'position_title' => 'Teacher',
                    'company' => 'Naga Learning Center',
                    'years_months' => '5 years',
                    'training_title' => 'Inclusive Education Training',
                    'training_hours' => 40,
                    'license_name' => 'Licensure Examination for Teachers',
                    'rating' => '91.50',
                    'valid_until' => null,
                    'remarks' => 'Application approved by the administrator.',
                    'resume_checked' => true,
                    'credentials_valid' => true,
                    'recommended' => true,
                    'days_ago' => 7,
                ],
                [
                    'job_position_id' => $adminAssistantId,
                    'status' => 'approved',
                    'full_name' => 'Miguel Bautista',
                    'email' => 'miguel.bautista@example.com',
                    'phone' => '09171234508',
                    'address' => 'Pili, Camarines Sur',
                    'disability' => null,
                    'ethnic_group' => null,
                    'degree' => 'Bachelor of Science in Information Technology',
                    'school' => 'Camarines Sur Polytechnic Colleges',
                    'year_graduated' => '2020',
                    'position_title' => 'Administrative Staff',
                    'company' => 'Provincial Government Office',
                    'years_months' => '3 years',
                    'training_title' => 'Office Productivity Tools',
                    'training_hours' => 24,
                    'license_name' => 'Civil Service Professional',
                    'rating' => '87.30',
                    'valid_until' => null,
                    'remarks' => 'Qualified applicant. Application approved.',
                    'resume_checked' => true,
                    'credentials_valid' => true,
                    'recommended' => true,
                    'days_ago' => 8,
                ],

                /*
                |--------------------------------------------------------------------------
                | REJECTED
                |--------------------------------------------------------------------------
                */

                [
                    'job_position_id' => $teacherId,
                    'status' => 'rejected',
                    'full_name' => 'Noel Garcia',
                    'email' => 'noel.garcia@example.com',
                    'phone' => '09171234509',
                    'address' => 'Masbate City, Masbate',
                    'disability' => null,
                    'ethnic_group' => null,
                    'degree' => 'Bachelor of Arts',
                    'school' => 'Masbate Colleges',
                    'year_graduated' => '2022',
                    'position_title' => 'Tutor',
                    'company' => 'Private Tutorial Center',
                    'years_months' => '1 year',
                    'training_title' => 'Basic Teaching Workshop',
                    'training_hours' => 8,
                    'license_name' => null,
                    'rating' => null,
                    'valid_until' => null,
                    'remarks' => 'Required teaching eligibility was not submitted.',
                    'resume_checked' => true,
                    'credentials_valid' => false,
                    'recommended' => false,
                    'days_ago' => 9,
                ],
                [
                    'job_position_id' => $educationSpecialistId,
                    'status' => 'rejected',
                    'full_name' => 'Sofia Ramos',
                    'email' => 'sofia.ramos@example.com',
                    'phone' => '09171234510',
                    'address' => 'Virac, Catanduanes',
                    'disability' => null,
                    'ethnic_group' => null,
                    'degree' => 'Bachelor of Science in Management',
                    'school' => 'Catanduanes State University',
                    'year_graduated' => '2023',
                    'position_title' => 'Office Assistant',
                    'company' => 'Private Company',
                    'years_months' => '6 months',
                    'training_title' => 'Basic Project Management',
                    'training_hours' => 8,
                    'license_name' => 'Civil Service Subprofessional',
                    'rating' => '78.20',
                    'valid_until' => null,
                    'remarks' => 'Applicant does not meet the required experience.',
                    'resume_checked' => true,
                    'credentials_valid' => true,
                    'recommended' => false,
                    'days_ago' => 10,
                ],
            ];

            /*
            |--------------------------------------------------------------------------
            | INSERT APPLICATIONS AND RELATED RECORDS
            |--------------------------------------------------------------------------
            */

            foreach ($applications as $index => $data) {
                $createdAt = now()->subDays($data['days_ago']);

                /*
                |--------------------------------------------------------------------------
                | Avoid duplicate seeded applications
                |--------------------------------------------------------------------------
                */

                $existingApplicationId = DB::table('applicant_profiles')
                    ->where('email', $data['email'])
                    ->value('application_id');

                if ($existingApplicationId) {
                    continue;
                }

                $applicationId = DB::table('applications')->insertGetId([
                    'job_position_id' => $data['job_position_id'],
                    'status' => $data['status'],

                    // These fields exist in your additional applications migration.
                    'resume_checked' => $data['resume_checked'] ?? false,
                    'credentials_valid' => $data['credentials_valid'] ?? false,
                    'recommended' => $data['recommended'] ?? false,

                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Applicant profile
                |--------------------------------------------------------------------------
                */

                DB::table('applicant_profiles')->insert([
                    'application_id' => $applicationId,
                    'full_name' => $data['full_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'address' => $data['address'],
                    'disability' => $data['disability'],
                    'ethnic_group' => $data['ethnic_group'],
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Education
                |--------------------------------------------------------------------------
                */

                DB::table('applicant_educations')->insert([
                    'application_id' => $applicationId,
                    'level' => 'College',
                    'school' => $data['school'],
                    'degree' => $data['degree'],
                    'year_graduated' => $data['year_graduated'],
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Experience
                |--------------------------------------------------------------------------
                */

                DB::table('applicant_experiences')->insert([
                    'application_id' => $applicationId,
                    'title' => $data['position_title'],
                    'company' => $data['company'],
                    'years_months' => $data['years_months'],
                    'details' => 'Sample work experience generated by the application seeder.',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Training
                |--------------------------------------------------------------------------
                */

                DB::table('applicant_trainings')->insert([
                    'application_id' => $applicationId,
                    'title' => $data['training_title'],
                    'hours' => $data['training_hours'],
                    'training_date' => $createdAt
                        ->copy()
                        ->subMonths(6)
                        ->startOfMonth()
                        ->toDateString(),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Eligibility
                |--------------------------------------------------------------------------
                */

                if (! empty($data['license_name'])) {
                    DB::table('applicant_eligibilities')->insert([
                        'application_id' => $applicationId,
                        'license_name' => $data['license_name'],
                        'rating' => $data['rating'],
                        'valid_until' => $data['valid_until'],
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Control number
                |--------------------------------------------------------------------------
                */

                DB::table('application_control_numbers')->insert([
                    'application_id' => $applicationId,
                    'control_number' => sprintf(
                        'DEPED-%s-%05d',
                        now()->format('Y'),
                        $applicationId
                    ),
                    'generated_by' => $adminId,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Initial pending status log
                |--------------------------------------------------------------------------
                */

                DB::table('application_status_logs')->insert([
                    'application_id' => $applicationId,
                    'status' => 'pending',
                    'remarks' => 'Application submitted successfully.',
                    'changed_by' => null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Evaluation record
                |--------------------------------------------------------------------------
                */

                if (in_array(
                    $data['status'],
                    ['evaluated', 'approved', 'rejected'],
                    true
                )) {
                    $evaluatedAt = $createdAt->copy()->addDay();

                    DB::table('application_evaluations')->insert([
                        'application_id' => $applicationId,
                        'evaluator_id' => $evaluatorId,
                        'resume_checked' => $data['resume_checked'],
                        'credentials_valid' => $data['credentials_valid'],
                        'recommended' => $data['recommended'],
                        'remarks' => $data['remarks'],
                        'evaluated_at' => $evaluatedAt,
                        'created_at' => $evaluatedAt,
                        'updated_at' => $evaluatedAt,
                    ]);

                    DB::table('application_status_logs')->insert([
                        'application_id' => $applicationId,
                        'status' => 'evaluated',
                        'remarks' => $data['remarks'],
                        'changed_by' => $evaluatorId,
                        'created_at' => $evaluatedAt,
                        'updated_at' => $evaluatedAt,
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Final approved or rejected status log
                |--------------------------------------------------------------------------
                */

                if (in_array($data['status'], ['approved', 'rejected'], true)) {
                    $decisionAt = $createdAt->copy()->addDays(2);

                    DB::table('application_status_logs')->insert([
                        'application_id' => $applicationId,
                        'status' => $data['status'],
                        'remarks' => $data['remarks'],
                        'changed_by' => $adminId,
                        'created_at' => $decisionAt,
                        'updated_at' => $decisionAt,
                    ]);
                }
            }
        });
    }
}
