<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE `devices`  ADD `age_old` VARCHAR(10) AFTER `age`');
        DB::statement('UPDATE devices SET `age_old` = `age`');
        DB::statement('ALTER TABLE `devices`  ADD `age_new` DECIMAL(5,2) UNSIGNED ZEROFILL NOT NULL DEFAULT 0 AFTER `age`');
        DB::statement('UPDATE devices SET `age_new` = `age`');
        DB::statement('ALTER TABLE `devices` DROP `age`');
        DB::statement('ALTER TABLE `devices` CHANGE `age_new` `age` DECIMAL(5,2) UNSIGNED ZEROFILL NOT NULL DEFAULT 0');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE `devices` DROP `age`');
        DB::statement('ALTER TABLE `devices` CHANGE `age_old` `age` VARCHAR(10)');
    }
};
