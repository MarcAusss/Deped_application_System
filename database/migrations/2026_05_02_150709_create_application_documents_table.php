<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    


    public function up(): void
    {
     Schema::create('application_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')->constrained()->cascadeOnDelete();

            $table->string('type');  
            $table->string('file_path');

            $table->timestamps();
        });
    }

    


    public function down(): void
    {
        Schema::dropIfExists('application_documents');
    }
};
