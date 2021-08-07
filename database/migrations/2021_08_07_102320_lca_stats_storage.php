<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class LcaStatsStorage extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stats_events', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('idevents')->index('idevents');
            $table->unsignedInteger('version')->index('version');
            $table->float('displacement', 4, 2);
            $table->float('ratio', 24, 16);
            $table->float('co2', 16, 8);
            $table->float('ewaste', 16, 8);
            $table->float('unpowered_waste', 16, 8);
            $table->unsignedInteger('fixed_devices')->default(0);
            $table->unsignedInteger('fixed_powered')->default(0);
            $table->unsignedInteger('fixed_unpowered')->default(0);
            $table->unsignedInteger('repairable_devices')->default(0);
            $table->unsignedInteger('dead_devices')->default(0);
            $table->unsignedInteger('no_weight')->default(0);
            $table->unsignedInteger('participants')->default(0);
            $table->unsignedInteger('volunteers')->default(0);
            $table->unsignedInteger('hours_volunteered')->default(0);
            $table->unsignedInteger('devices_powered')->default(0);
            $table->unsignedInteger('devices_unpowered')->default(0);
            $table->timestamps();
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stats_events');
    }
}
