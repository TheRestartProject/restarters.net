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
        Schema::create('devices_urls', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('device_id');
            $table->foreign('device_id')->references('iddevices')->on('devices');
            $table->string('url');
            $table->tinyInteger('source')->nullable();
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->tinyInteger('end_of_life')->after('do_it_yourself')->nullable();
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
        Schema::dropIfExists('devices_urls');

        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('end_of_life');
            $table->dropColumn('parts_provider');
        });
    }
}
