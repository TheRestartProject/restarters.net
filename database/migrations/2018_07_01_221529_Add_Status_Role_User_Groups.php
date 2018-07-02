<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusRoleUserGroups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('users_groups', function (Blueprint $table) {
          $table->string('status', 50)->nullable();
          $table->tinyInteger('role')->default(4);
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::table('users_groups', function (Blueprint $table) {
          $table->dropColumn('status');
          $table->dropColumn('role');
      });
    }
}
