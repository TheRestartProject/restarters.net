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

        DB::table('barriers')->insert([
          'barrier' => 'Spare parts not available',
        ]);
        DB::table('barriers')->insert([
          'barrier' => 'Spare parts too expensive',
        ]);
        DB::table('barriers')->insert([
          'barrier' => 'No way to open the product',
        ]);
        DB::table('barriers')->insert([
          'barrier' => 'Repair information not available',
        ]);
        DB::table('barriers')->insert([
          'barrier' => 'Lack of equipment',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barriers');
        Schema::dropIfExists('devices_urls');
        Schema::dropIfExists('devices_barriers');

        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('parts_provider');
        });
    }
};
