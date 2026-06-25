<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
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
