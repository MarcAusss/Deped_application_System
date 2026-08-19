<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('applicant_trainings', function (Blueprint $table) {
            $table->date('training_end_date')->nullable()->after('training_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applicant_trainings', function (Blueprint $table) {
            $table->dropColumn('training_end_date');
        });
    }
};
