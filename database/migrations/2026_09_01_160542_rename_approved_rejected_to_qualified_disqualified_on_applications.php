<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Widen the enum first so both old and new values are valid while data migrates.
        DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM('pending', 'evaluated', 'excluded', 'approved', 'rejected', 'qualified', 'disqualified') NOT NULL DEFAULT 'pending'");

        DB::statement("UPDATE applications SET status = 'qualified' WHERE status = 'approved'");
        DB::statement("UPDATE applications SET status = 'disqualified' WHERE status = 'rejected'");

        DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM('pending', 'evaluated', 'excluded', 'qualified', 'disqualified') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM('pending', 'evaluated', 'excluded', 'qualified', 'disqualified', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");

        DB::statement("UPDATE applications SET status = 'approved' WHERE status = 'qualified'");
        DB::statement("UPDATE applications SET status = 'rejected' WHERE status = 'disqualified'");

        DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM('pending', 'evaluated', 'excluded', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");
    }
};
