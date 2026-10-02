<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Login attempt/lockout tracking for the hand-rolled public/evaluator-portal
     * pure-PHP evaluator portal (Phase 2 of the pure-PHP rewrite). Same shape
     * as portal_login_throttle (Phase 1), kept as a separate table since the
     * two portals authenticate against different tables (users vs applicants)
     * and must stay fully independent.
     */
    public function up(): void
    {
        Schema::create('evaluator_login_throttle', function (Blueprint $table) {
            $table->id();
            $table->string('throttle_key', 191)->unique();
            $table->unsignedInteger('attempts')->default(0);
            $table->dateTime('locked_until')->nullable();
            $table->unsignedInteger('lockout_level')->default(0);
            $table->dateTime('lockout_level_expires_at')->nullable();
            $table->dateTime('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluator_login_throttle');
    }
};
