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
            $table->json('attachment_paths')->nullable()->after('attachment_path');
        });

        DB::table('job_positions')->whereNotNull('attachment_path')->get(['id', 'attachment_path'])->each(function ($row) {
            DB::table('job_positions')
                ->where('id', $row->id)
                ->update(['attachment_paths' => json_encode([$row->attachment_path])]);
        });

        Schema::table('job_positions', function (Blueprint $table) {
            $table->dropColumn('attachment_path');
        });
    }

    public function down(): void
    {
        Schema::table('job_positions', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('csc_publication_path');
        });

        DB::table('job_positions')->whereNotNull('attachment_paths')->get(['id', 'attachment_paths'])->each(function ($row) {
            $paths = json_decode($row->attachment_paths, true) ?? [];
            DB::table('job_positions')
                ->where('id', $row->id)
                ->update(['attachment_path' => $paths[0] ?? null]);
        });

        Schema::table('job_positions', function (Blueprint $table) {
            $table->dropColumn('attachment_paths');
        });
    }
};
