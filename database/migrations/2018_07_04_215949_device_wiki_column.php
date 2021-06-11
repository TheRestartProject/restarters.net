<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeviceWikiColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('devices', function (Blueprint $table) {
          $table->integer('wiki')->after('repaired_by')->nullable();
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::table('devices', function (Blueprint $table) {
          $table->dropColumn('wiki');
      });
    }
}
