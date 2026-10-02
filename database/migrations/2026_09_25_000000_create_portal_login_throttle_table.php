<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Login attempt/lockout tracking for the hand-rolled public/portal pure-PHP
     * applicant portal (Phase 1 of the pure-PHP rewrite). There is no Laravel
     * Cache/RateLimiter available to those plain PHP pages, so this table is
     * the direct equivalent, keyed by "email|ip" the same way the existing
     * Laravel ApplicantAuthController's throttleKey() is.
     */
    public function up(): void
    {
        Schema::create('portal_login_throttle', function (Blueprint $table) {
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
        Schema::dropIfExists('portal_login_throttle');
    }
};
