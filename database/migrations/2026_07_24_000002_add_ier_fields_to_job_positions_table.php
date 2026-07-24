<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_positions', function (Blueprint $table) {
            $table->string('salary_grade', 50)->nullable()->after('description');
            $table->decimal('monthly_salary', 12, 2)->nullable()->after('salary_grade');
            $table->text('education_requirement')->nullable()->after('monthly_salary');
            $table->text('training_requirement')->nullable()->after('education_requirement');
            $table->text('experience_requirement')->nullable()->after('training_requirement');
            $table->text('eligibility_requirement')->nullable()->after('experience_requirement');
        });
    }

    public function down(): void
    {
        Schema::table('job_positions', function (Blueprint $table) {
            $table->dropColumn([
                'salary_grade',
                'monthly_salary',
                'education_requirement',
                'training_requirement',
                'experience_requirement',
                'eligibility_requirement',
            ]);
        });
    }
};
