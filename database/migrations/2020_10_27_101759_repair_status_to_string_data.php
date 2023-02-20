<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('devices', 'repair_status_str')) {
            Schema::table('devices', function (Blueprint $table) {
                $table->enum('repair_status_str', ['Unknown', 'Fixed', 'Repairable', 'End of life'])->after('repair_status')->index();
            });
        }
        // Ideally the update and triggers would use REPAIR_STATUS_TO_STR()
        // but there are issues with definer/permissions

        DB::table('devices')
                ->where('repair_status', 1)
                ->update(['repair_status_str' => 'Fixed']);
        DB::table('devices')
                ->where('repair_status', 2)
                ->update(['repair_status_str' => 'Repairable']);
        DB::table('devices')
                ->where('repair_status', 3)
                ->update(['repair_status_str' => 'End of life']);
        DB::table('devices')
                ->where('repair_status', 0)
                ->update(['repair_status_str' => 'Unknown']);
        DB::unprepared("CREATE TRIGGER `repair_status_str_up`
BEFORE UPDATE ON `devices` FOR EACH ROW
SET NEW.repair_status_str = CASE
        WHEN NEW.repair_status = 1 THEN 'Fixed'
        WHEN NEW.repair_status = 2 THEN 'Repairable'
        WHEN NEW.repair_status = 3 THEN 'End of life'
        ELSE 'Unknown'
END;
");
        DB::unprepared("CREATE TRIGGER `repair_status_str_in`
BEFORE INSERT ON `devices` FOR EACH ROW
SET NEW.repair_status_str = CASE
        WHEN NEW.repair_status = 1 THEN 'Fixed'
        WHEN NEW.repair_status = 2 THEN 'Repairable'
        WHEN NEW.repair_status = 3 THEN 'End of life'
        ELSE 'Unknown'
END;
");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared('DROP TRIGGER IF EXISTS `repair_status_str_in`');
        DB::unprepared('DROP TRIGGER IF EXISTS `repair_status_str_up`');
        if (Schema::hasColumn('devices', 'repair_status_str')) {
            Schema::table('devices', function (Blueprint $table) {
                $table->dropColumn('repair_status_str');
            });
        }
    }
};
