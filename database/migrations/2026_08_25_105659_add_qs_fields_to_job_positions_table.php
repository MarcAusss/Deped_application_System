<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_positions', function (Blueprint $table) {
            $table->decimal('min_experience_years', 5, 2)->nullable()->after('experience_requirement');
            $table->unsignedInteger('min_training_hours')->nullable()->after('training_requirement');
        });
    }

    public function down(): void
    {
        Schema::table('job_positions', function (Blueprint $table) {
            $table->dropColumn(['min_experience_years', 'min_training_hours']);
        });
    }
};
