<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdditionalDeviceFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('barriers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('barrier');
        });

        Schema::create('devices_barriers', function (Blueprint $table) {
            $table->integer('device_id');
            $table->foreign('device_id')->references('iddevices')->on('devices');
            $table->tinyInteger('barrier_id');
        });

        Schema::create('devices_urls', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('device_id');
            $table->foreign('device_id')->references('iddevices')->on('devices');
            $table->string('url');
            $table->tinyInteger('source')->nullable();
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->tinyInteger('parts_provider')->after('spare_parts')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('barriers');
        Schema::dropIfExists('devices_urls');
        Schema::dropIfExists('devices_barriers');

        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('parts_provider');
        });
    }
}
