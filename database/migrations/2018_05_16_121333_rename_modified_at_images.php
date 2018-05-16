<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameModifiedAtImages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('images', function (Blueprint $table) {
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
      Schema::table('images', function (Blueprint $table) {
          $table->renameColumn('updated_at', 'modified_at');
      });
    }
}
