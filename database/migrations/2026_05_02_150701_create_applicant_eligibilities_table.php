<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    


    public function up(): void
    {
        Schema::create('applicant_eligibilities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')->constrained()->cascadeOnDelete();

            $table->string('license_name')->nullable();
            $table->string('rating')->nullable();
            $table->date('valid_until')->nullable();

            $table->timestamps();
        });
    }

    


    public function down(): void
    {
        Schema::dropIfExists('applicant_eligibilities');
    }
};
