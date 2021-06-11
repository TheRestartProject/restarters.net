<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UsersLocationAgeGender extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('users', function (Blueprint $table) {
          $table->string('location')->nullable();
          $table->string('age')->default('N/A');
          $table->string('gender')->default('N/A');
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::table('users', function (Blueprint $table) {
          $table->dropColumn('location');
          $table->dropColumn('age');
          $table->dropColumn('gender');
      });
    }
}
