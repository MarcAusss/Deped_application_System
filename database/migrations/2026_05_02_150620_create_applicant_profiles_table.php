<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    


    public function up(): void
    {
       Schema::create('applicant_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')->constrained()->cascadeOnDelete();

            $table->string('full_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();

            $table->string('disability')->nullable();
            $table->string('ethnic_group')->nullable();

            $table->timestamps();
        });
    }

    


    public function down(): void
    {
        Schema::dropIfExists('applicant_profiles');
    }
};
