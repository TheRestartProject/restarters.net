<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RepairStatusToString extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared("DROP FUNCTION IF EXISTS `REPAIR_STATUS_TO_STRING`");
        DB::unprepared("
CREATE FUNCTION `REPAIR_STATUS_TO_STRING`(`id` INT)
RETURNS VARCHAR(12) CHARSET utf8
DETERMINISTIC
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
    public function down()
    {
        DB::unprepared("DROP FUNCTION IF EXISTS `REPAIR_STATUS_TO_STRING`");
    }
}
