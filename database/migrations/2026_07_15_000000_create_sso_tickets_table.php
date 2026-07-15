<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One-time tickets exchanged by the SPA for a Laravel web session at
 * GET /auth/bridge — the hop that keeps Discourse SSO and MediaWiki silent
 * login working under token-based SPA auth. See docs/nuxt-migration/design.md §4.3.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sso_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('ticket_hash', 64)->unique();
            $table->dateTime('expires_at');
            $table->dateTime('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sso_tickets');
    }
};
