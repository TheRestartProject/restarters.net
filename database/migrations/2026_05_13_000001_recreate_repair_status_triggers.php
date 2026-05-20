<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL 8.0 with binary logging enabled blocks CREATE TRIGGER for non-SUPER
        // users unless this flag is set. Best-effort: if we don't have SUPER the
        // server my.cnf must already have it (e.g. production's custom.cnf).
        try {
            DB::statement('SET GLOBAL log_bin_trust_function_creators = 1');
        } catch (\Exception $e) {
            // No SUPER privilege — rely on server-side configuration.
        }

        // The 2025_01_08 migration dropped these triggers to fix Docker Compose
        // test permissions. Without them, repair_status_str is always 'Unknown'
        // for new/updated devices. Recreate them here.
        DB::unprepared('DROP TRIGGER IF EXISTS `repair_status_str_up`');
        DB::unprepared('DROP TRIGGER IF EXISTS `repair_status_str_in`');

        DB::unprepared("CREATE TRIGGER `repair_status_str_up`
BEFORE UPDATE ON `devices` FOR EACH ROW
SET NEW.repair_status_str = CASE
    WHEN NEW.repair_status = 1 THEN 'Fixed'
    WHEN NEW.repair_status = 2 THEN 'Repairable'
    WHEN NEW.repair_status = 3 THEN 'End of life'
    ELSE 'Unknown'
END");

        DB::unprepared("CREATE TRIGGER `repair_status_str_in`
BEFORE INSERT ON `devices` FOR EACH ROW
SET NEW.repair_status_str = CASE
    WHEN NEW.repair_status = 1 THEN 'Fixed'
    WHEN NEW.repair_status = 2 THEN 'Repairable'
    WHEN NEW.repair_status = 3 THEN 'End of life'
    ELSE 'Unknown'
END");

        // Backfill any rows that were created/updated after the triggers were
        // dropped (repair_status_str stuck at 'Unknown' despite a non-zero status)
        DB::statement("
            UPDATE devices
            SET repair_status_str = CASE
                WHEN repair_status = 1 THEN 'Fixed'
                WHEN repair_status = 2 THEN 'Repairable'
                WHEN repair_status = 3 THEN 'End of life'
                ELSE 'Unknown'
            END
            WHERE repair_status_str != CASE
                WHEN repair_status = 1 THEN 'Fixed'
                WHEN repair_status = 2 THEN 'Repairable'
                WHEN repair_status = 3 THEN 'End of life'
                ELSE 'Unknown'
            END
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS `repair_status_str_up`');
        DB::unprepared('DROP TRIGGER IF EXISTS `repair_status_str_in`');
    }
};
