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
        Schema::table('applicant_experiences', function (Blueprint $table) {
            $table->string('first_day')->nullable()->after('company');
            $table->string('last_day')->nullable()->after('first_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applicant_experiences', function (Blueprint $table) {
            $table->dropColumn(['first_day', 'last_day']);
        });
    }
};
