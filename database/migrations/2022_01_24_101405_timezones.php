<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Timezones extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('timezone', 64)->comment('TZ database name')->nullable()->default(null);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->string('timezone', 64)->comment('TZ database name')->nullable()->default(null);
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
            $table->dropColumn('timezone');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('timezone');
        });
    }
}
