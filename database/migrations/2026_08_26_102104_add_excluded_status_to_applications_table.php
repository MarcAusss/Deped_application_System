<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM('pending', 'evaluated', 'excluded', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("UPDATE applications SET status = 'pending' WHERE status = 'excluded'");
        DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM('pending', 'evaluated', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");
    }
};
