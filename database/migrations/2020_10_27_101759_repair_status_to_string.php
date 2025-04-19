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
    public function up(): void
    {
        DB::unprepared('DROP FUNCTION IF EXISTS `REPAIR_STATUS_TO_STRING`');
        // Might need to set definer
//        DB::unprepared("CREATE DEFINER='root'@'localhost' FUNCTION `REPAIR_STATUS_TO_STRING`(`id` INT)
        DB::unprepared("CREATE FUNCTION `REPAIR_STATUS_TO_STRING`(`id` INT)
RETURNS varchar(12) CHARSET utf8
    NO SQL
    DETERMINISTIC
    SQL SECURITY INVOKER
BEGIN
  DECLARE res VARCHAR(12);
  CASE id
   WHEN 1 THEN SET res = 'Fixed';
   WHEN 2 THEN SET res = 'Repairable';
   WHEN 3 THEN SET res = 'End of life';
   ELSE SET res = 'Unknown';
  END CASE;
  RETURN res;
END
");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        DB::unprepared('DROP FUNCTION IF EXISTS `REPAIR_STATUS_TO_STRING`');
    }
};
