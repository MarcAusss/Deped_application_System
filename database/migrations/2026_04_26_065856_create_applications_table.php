<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    


    public function up(): void
    {
       Schema::create('applications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('job_position_id')->constrained()->cascadeOnDelete();

            $table->enum('status', ['pending', 'evaluated', 'approved', 'rejected'])
                ->default('pending');

            $table->timestamps();
        });
    }

    


    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
