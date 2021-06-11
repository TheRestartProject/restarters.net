<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MediaWikiUsername extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('users', function (Blueprint $table) {
          $table->string('mediawiki')->default(null)->nullable()->change();
      });

      DB::table('users')->where('mediawiki', 0)->update([
        'mediawiki' => null
      ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::table('users', function (Blueprint $table) {
          $table->integer('mediawiki')->default(0)->change();
      });
    }
}
