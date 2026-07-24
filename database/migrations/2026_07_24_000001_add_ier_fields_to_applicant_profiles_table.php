<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicant_profiles', function (Blueprint $table) {
            $table->date('birth_date')->nullable()->after('address');
            $table->string('sex', 30)->nullable()->after('birth_date');
            $table->string('civil_status', 50)->nullable()->after('sex');
            $table->string('religion')->nullable()->after('civil_status');
        });
    }

    public function down(): void
    {
        Schema::table('applicant_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'birth_date',
                'sex',
                'civil_status',
                'religion',
            ]);
        });
    }
};
