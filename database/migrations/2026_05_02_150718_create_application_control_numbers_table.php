<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    


    public function up(): void
    {
        Schema::create('application_control_numbers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')->constrained()->cascadeOnDelete();

            $table->string('control_number')->unique();

            $table->foreignId('generated_by')->nullable()->constrained('users');

            $table->timestamps();
        });
    }

    


    public function down(): void
    {
        Schema::dropIfExists('application_control_numbers');
    }
};
