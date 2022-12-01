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
        DB::statement('ALTER TABLE `devices`  ADD `estimate_old` VARCHAR(10) AFTER `estimate`');
        DB::statement('UPDATE devices SET `estimate_old` = `estimate`');
        DB::statement('ALTER TABLE `devices`  ADD `estimate_new` DECIMAL(6,3) UNSIGNED ZEROFILL NOT NULL DEFAULT 0 AFTER `estimate`');
        DB::statement('UPDATE devices SET `estimate_new` = `estimate`');
        DB::statement('ALTER TABLE `devices` DROP `estimate`');
        DB::statement('ALTER TABLE `devices` CHANGE `estimate_new` `estimate` DECIMAL(6,3) UNSIGNED ZEROFILL NOT NULL DEFAULT 0');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `devices` DROP `estimate`');
        DB::statement('ALTER TABLE `devices` CHANGE `estimate_old` `estimate` VARCHAR(10)');
    }
};
