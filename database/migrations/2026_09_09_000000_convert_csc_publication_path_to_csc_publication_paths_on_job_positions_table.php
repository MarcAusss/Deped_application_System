<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_positions', function (Blueprint $table) {
            $table->json('csc_publication_paths')->nullable()->after('csc_publication_path');
        });

        DB::table('job_positions')->whereNotNull('csc_publication_path')->get(['id', 'csc_publication_path'])->each(function ($row) {
            DB::table('job_positions')
                ->where('id', $row->id)
                ->update(['csc_publication_paths' => json_encode([$row->csc_publication_path])]);
        });

        Schema::table('job_positions', function (Blueprint $table) {
            $table->dropColumn('csc_publication_path');
        });
    }

    public function down(): void
    {
        Schema::table('job_positions', function (Blueprint $table) {
            $table->string('csc_publication_path')->nullable()->after('attachment_paths');
        });

        DB::table('job_positions')->whereNotNull('csc_publication_paths')->get(['id', 'csc_publication_paths'])->each(function ($row) {
            $paths = json_decode($row->csc_publication_paths, true) ?? [];
            DB::table('job_positions')
                ->where('id', $row->id)
                ->update(['csc_publication_path' => $paths[0] ?? null]);
        });

        Schema::table('job_positions', function (Blueprint $table) {
            $table->dropColumn('csc_publication_paths');
        });
    }
};
