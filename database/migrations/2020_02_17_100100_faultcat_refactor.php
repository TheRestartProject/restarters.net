<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FaultcatRefactor extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('devices_faults_adjudicated', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('iddevices')->index();
            $table->string('fault_type', 64)->index();
            $table->timestamps();
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });

        Schema::table('devices_faults', function (Blueprint $table) {
            $table->string('fault_type', 64)->change();
        });

        Schema::rename('devices_faults', 'devices_faults_events');

        DB::statement("ALTER TABLE devices_faults_events CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        DB::statement("ALTER TABLE devices_faults_opinions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        Schema::table('devices', function (Blueprint $table) {
          $table->string('fault_type', 64)->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('devices_faults_adjudicated');
        
        Schema::rename('devices_faults_events', 'devices_faults');
        
        Schema::table('devices_faults', function (Blueprint $table) {
            $table->string('fault_type', 32)->change();
        });
        
        DB::statement("ALTER TABLE devices_faults CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci");
        DB::statement("ALTER TABLE devices_faults_opinions CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci");        

        Schema::table('devices', function (Blueprint $table) {
          $table->dropColumn('fault_type');
        });
    }

}
