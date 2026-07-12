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
        Schema::create('application_evaluations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('evaluator_id')
                ->constrained('users');

            // Checklist
            $table->boolean('resume_checked')->default(false);
            $table->boolean('credentials_valid')->default(false);
            $table->boolean('recommended')->default(false);

            // Optional
            $table->text('remarks')->nullable();

            $table->timestamp('evaluated_at')->nullable();

            $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_evaluations');
    }
};
