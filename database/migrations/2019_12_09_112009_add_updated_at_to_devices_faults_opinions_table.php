<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUpdatedAtToDevicesFaultsOpinionsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        if (Schema::hasTable('devices_faults_opinions')) {
            if (!Schema::hasColumn('devices_faults_opinions', 'updated_at')) {
                Schema::table('devices_faults_opinions', function (Blueprint $table) {
                    $table->datetime('updated_at')->after('session_id');
                });
            }
        } else {
            Schema::create('devices_faults_opinions', function (Blueprint $table) {
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
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('devices_faults_opinions', function (Blueprint $table) {
            $table->dropColumn('updated_at');
        });
    }

}
