<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DefaultVolunteersValue extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('events', function (Blueprint $table) {
          $table->integer('volunteers')->nullable(false)->default(0)->change();
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::table('events', function (Blueprint $table) {
          $table->integer('volunteers')->nullable()->change();
      });
    }
}
