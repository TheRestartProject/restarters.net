<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PasswordRecovery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE `users`
                         ADD COLUMN `recovery` VARCHAR(45) NULL AFTER `role`,
                         ADD COLUMN `recovery_expires` TIMESTAMP NULL AFTER `recovery`;'
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
