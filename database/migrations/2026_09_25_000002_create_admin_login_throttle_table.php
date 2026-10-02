<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Login attempt/lockout tracking for the hand-rolled public/admin-portal
     * pure-PHP admin portal (Phase 3 of the pure-PHP rewrite). Same shape as
     * portal_login_throttle/evaluator_login_throttle, kept as its own table
     * so all three portals stay fully independent.
     */
    public function up(): void
    {
        Schema::create('admin_login_throttle', function (Blueprint $table) {
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
        Schema::dropIfExists('admin_login_throttle');
    }
};
