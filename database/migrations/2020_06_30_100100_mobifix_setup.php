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
    public function up(): void
    {
        Schema::create('devices_faults_mobiles_opinions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('iddevices')->index();
            $table->string('fault_type', 64)->index();
            $table->string('session_id', 191);
            $table->ipAddress('ip_address');
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamps();
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });

        Schema::create('devices_faults_mobiles_adjudicated', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('iddevices')->index();
            $table->string('fault_type', 64)->index();
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
    public function down(): void
    {
        Schema::dropIfExists('devices_faults_mobiles_adjudicated');
        Schema::dropIfExists('devices_faults_mobiles_opinions');
    }
};
