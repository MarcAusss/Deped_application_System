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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_position_id');
            $table->string('full_name');
            $table->string('email');
            $table->string('phone_number');
            $table->text('resume');
            $table->enum('status', ['pending', 'evaluated', 'rejected', 'approved'])->default('pending');
            $table->boolean('evaluated_by_evaluator')->default(false);
            $table->boolean('final_reviewed_by_admin')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
