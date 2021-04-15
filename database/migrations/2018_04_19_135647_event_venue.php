<?php

use Illuminate\Database\Migrations\Migration;

class EventVenue extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement(
            'ALTER TABLE `events`
                        ADD COLUMN `venue` VARCHAR(255) NULL AFTER `end`;'
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
