<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users_preferences', function (Blueprint $table) {
          $table->integer('preference_id')->unsigned();
          $table->foreign('preference_id')->references('id')->on('preferences');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users_preferences', function (Blueprint $table) {
            $table->dropColumn('preference_id');
        });
    }
}
