<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::drop('devices_urls');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('devices_urls', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('device_id');
            $table->foreign('device_id')->references('iddevices')->on('devices');
            $table->string('url');
            $table->tinyInteger('source')->nullable();
        });
    }
};
