<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicant_trainings', function (Blueprint $table) {
            $table->date('training_date')->nullable()->after('hours');
        });
    }

    public function down(): void
    {
        Schema::table('applicant_trainings', function (Blueprint $table) {
            $table->dropColumn('training_date');
        });
    }
};
