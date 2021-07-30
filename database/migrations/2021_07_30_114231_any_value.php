<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AnyValue extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection()->getpdo()->exec("DROP FUNCTION IF EXISTS ANY_VALUE;");
        DB::unprepared('CREATE AGGREGATE FUNCTION `ANY_VALUE`(x LONGBLOB) RETURNS longblob
BEGIN
 LOOP
  FETCH GROUP NEXT ROW;
  RETURN x;
 END LOOP; 
END;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection()->getpdo()->exec("DROP FUNCTION IF EXISTS ANY_VALUE");
    }
}
