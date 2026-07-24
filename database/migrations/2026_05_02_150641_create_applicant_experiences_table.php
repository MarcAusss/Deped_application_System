<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    


    public function up(): void
    {
        Schema::create('applicant_experiences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->string('company')->nullable();
            $table->string('years_months')->nullable();
            $table->text('details')->nullable();

            $table->timestamps();
        });
    }

    


    public function down(): void
    {
        Schema::dropIfExists('applicant_experiences');
    }
};
