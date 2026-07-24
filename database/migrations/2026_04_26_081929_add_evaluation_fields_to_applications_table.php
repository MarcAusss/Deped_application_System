<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    


    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->boolean('resume_checked')->default(false);
            $table->boolean('credentials_valid')->default(false);
            $table->boolean('recommended')->default(false);
        });
    }

    


    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
             
        });
    }
};
