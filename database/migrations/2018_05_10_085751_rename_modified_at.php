<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameModifiedAt extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('groups', function (Blueprint $table) {
          $table->renameColumn('modified_at', 'updated_at');
      });
      Schema::table('events', function (Blueprint $table) {
          $table->renameColumn('modified_at', 'updated_at');
      });
      Schema::table('devices', function (Blueprint $table) {
          $table->renameColumn('modified_at', 'updated_at');
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::table('groups', function (Blueprint $table) {
          $table->renameColumn('updated_at', 'modified_at');
      });
      Schema::table('events', function (Blueprint $table) {
          $table->renameColumn('updated_at', 'modified_at');
      });
      Schema::table('devices', function (Blueprint $table) {
          $table->renameColumn('updated_at', 'modified_at');
      });
    }
}
