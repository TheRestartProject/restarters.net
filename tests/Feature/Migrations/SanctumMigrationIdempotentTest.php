<?php

namespace Tests\Feature\Migrations;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Regression test for the preview/prod deploy failures where restoring a
 * database that already had a branch-introduced table (created out-of-band,
 * with no migrations row) made the unguarded create-table migration re-run and
 * fail with "1050 Table '...' already exists".
 *
 * Every new-table migration the nuxt-client branch adds must be guarded with
 * Schema::hasTable so it is a no-op when the table already exists but still
 * creates it on fresh installs (CI, local, phpunit).
 */
class SanctumMigrationIdempotentTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function newTableMigrations(): array
    {
        return [
            'personal_access_tokens' => [
                'personal_access_tokens',
                '2019_12_14_000001_create_personal_access_tokens_table.php',
            ],
            'sso_tickets' => [
                'sso_tickets',
                '2026_07_15_000000_create_sso_tickets_table.php',
            ],
        ];
    }

    /**
     * @dataProvider newTableMigrations
     */
    public function testCreateTableMigrationIsIdempotent(string $table, string $file): void
    {
        // The table is present in the migrated test database, mirroring the
        // restored-production state that triggered the failure.
        $this->assertTrue(
            Schema::hasTable($table),
            "$table should already exist in the migrated database"
        );

        $migration = require database_path("migrations/$file");

        // Re-running up() against a database that already has the table must not
        // throw (an unguarded create would raise SQLSTATE 42S01).
        $migration->up();

        $this->assertTrue(Schema::hasTable($table));
    }
}
