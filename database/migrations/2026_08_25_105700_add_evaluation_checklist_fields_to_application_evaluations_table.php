<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_evaluations', function (Blueprint $table) {
            $table->json('documentary_mandatory')->nullable()->after('application_id');
            $table->json('documentary_other')->nullable()->after('documentary_mandatory');

            $table->boolean('qs_education_met')->nullable()->after('documentary_other');
            $table->boolean('qs_experience_met')->nullable()->after('qs_education_met');
            $table->boolean('qs_training_met')->nullable()->after('qs_experience_met');
            $table->boolean('qs_eligibility_met')->nullable()->after('qs_training_met');

            $table->string('result')->default('pending_document_review')->after('qs_eligibility_met');
        });
    }

    public function down(): void
    {
        Schema::table('application_evaluations', function (Blueprint $table) {
            $table->dropColumn([
                'documentary_mandatory',
                'documentary_other',
                'qs_education_met',
                'qs_experience_met',
                'qs_training_met',
                'qs_eligibility_met',
                'result',
            ]);
        });
    }
};
