<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Our copy of Laravel Sanctum's personal_access_tokens migration, guarded with
 * Schema::hasTable so it is idempotent. The vendor migration is disabled via
 * Sanctum::ignoreMigrations() (see AppServiceProvider::register): production
 * had this table created out-of-band without a corresponding migrations row, so
 * on a restored copy the unguarded vendor migration re-ran and failed with
 * "1050 Table 'personal_access_tokens' already exists". On a fresh database
 * (CI, local, phpunit) the table does not exist, so it is created as normal.
 *
 * Same filename/timestamp as the vendor migration so the recorded migration
 * name and ordering are unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('personal_access_tokens')) {
            return;
        }

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
