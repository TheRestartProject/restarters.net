<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class LatitudeLongitudeThreeDigits extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE `users` CHANGE `longitude` `longitude` DECIMAL(10,7), CHANGE `latitude` `latitude` DECIMAL(10,7) NULL DEFAULT NULL;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `users` CHANGE `longitude` `longitude` DECIMAL(10,8), CHANGE `latitude` `latitude` DECIMAL(10,8) NULL DEFAULT NULL;');
    }
}
