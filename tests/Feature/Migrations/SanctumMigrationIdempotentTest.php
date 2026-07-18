<?php

namespace Tests\Feature\Migrations;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Regression test for the preview/prod deploy failure where restoring a
 * database that already had personal_access_tokens (created out-of-band, with
 * no migrations row) made Sanctum's unguarded vendor migration re-run and fail
 * with "1050 Table 'personal_access_tokens' already exists".
 *
 * Our published, guarded copy (database/migrations/2019_12_14_000001_create_
 * personal_access_tokens_table.php, with the vendor one disabled via
 * Sanctum::ignoreMigrations) must be a no-op when the table already exists.
 */
class SanctumMigrationIdempotentTest extends TestCase
{
    public function testPersonalAccessTokensMigrationIsIdempotent(): void
    {
        // The table is present in the migrated test database, mirroring the
        // restored-production state that triggered the failure.
        $this->assertTrue(
            Schema::hasTable('personal_access_tokens'),
            'personal_access_tokens should already exist in the migrated database'
        );

        $migration = require database_path(
            'migrations/2019_12_14_000001_create_personal_access_tokens_table.php'
        );

        // Re-running up() against a database that already has the table must not
        // throw (the unguarded vendor migration would raise SQLSTATE 42S01).
        $migration->up();

        $this->assertTrue(Schema::hasTable('personal_access_tokens'));
    }
}
