<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('devices_misc_opinions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('iddevices')->index();
            $table->string('category', 64)->index();
            $table->boolean('eee');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('session_id', 191);
            $table->ipAddress('ip_address');
            $table->timestamps();
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });

        Schema::create('devices_misc_adjudicated', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('iddevices')->index();
            $table->string('category', 64)->index();
            $table->boolean('eee');
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
        Schema::dropIfExists('devices_misc_opinions');
        Schema::dropIfExists('devices_misc_adjudicated');
    }
};
