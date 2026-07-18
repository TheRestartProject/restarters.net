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
        // Guarded with hasTable: production has this table created out-of-band
        // (no migrations row), so on a restored copy the create re-ran and hit
        // "1050 Table 'sso_tickets' already exists". No-op when present, still
        // creates it on fresh installs (CI, local, phpunit).
        if (Schema::hasTable('sso_tickets')) {
            return;
        }

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
