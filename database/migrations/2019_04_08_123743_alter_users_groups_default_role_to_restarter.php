<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterUsersGroupsDefaultRoleToRestarter extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Using raw SQL as tinyint columns can't be altered.
        // See https://laravel.com/docs/5.6/migrations#modifying-columns
        DB::statement("ALTER TABLE `users_groups` CHANGE `role` `role` TINYINT(4) NOT NULL DEFAULT '4'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE `users_groups` CHANGE `role` `role` TINYINT(4) NOT NULL DEFAULT '3'");
    }
}
