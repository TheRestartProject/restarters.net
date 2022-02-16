<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MoreTimezones extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table)
        {
            $table->dropColumn('event_date_old');
            $table->dropColumn('start_old');
            $table->dropColumn('end_old');
            $table->dropColumn('event_date');
            $table->dropColumn('start');
            $table->dropColumn('end');
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
            $table->date('event_date_old')->comment('Old data before RES-1624')->nullable()->default(null);
            $table->time('start_old')->comment('Old data before RES-1624')->nullable()->default(null);
            $table->time('end_old')->comment('Old data before RES-1624')->nullable()->default(null);
            $table->date('event_date')->nullable()->default(null);
            $table->time('start')->nullable()->default(null);
            $table->time('end')->nullable()->default(null);
        });
    }
}
