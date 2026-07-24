<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    


    public function up(): void
    {
        Schema::create('applicant_educations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')->constrained()->cascadeOnDelete();

            $table->string('level');  
            $table->string('school')->nullable();
            $table->string('degree')->nullable();
            $table->string('year_graduated')->nullable();

            $table->timestamps();
        });
    }

    


    public function down(): void
    {
        Schema::dropIfExists('applicant_educations');
    }
};
