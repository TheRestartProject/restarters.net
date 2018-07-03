<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusRoleUserEvents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('events_users', function (Blueprint $table) {
          $table->string('status', 50)->nullable();
          $table->tinyInteger('role')->default(3);
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::table('events_users', function (Blueprint $table) {
          $table->dropColumn('status');
          $table->dropColumn('role');
      });
    }
}
