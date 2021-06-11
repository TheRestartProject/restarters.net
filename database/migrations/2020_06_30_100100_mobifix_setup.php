<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MobifixSetup extends Migration {

    /**
     * Run the migrations.
     * 
     * @return void
     */
    public function up() {

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
    public function down() {
        Schema::dropIfExists('devices_faults_mobiles_adjudicated');
        Schema::dropIfExists('devices_faults_mobiles_opinions');

    }

}
