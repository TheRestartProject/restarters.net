<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Includes missed migrations for tables
     * `devices_faults` and `devices_faults_opinions`.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('devices_faults')) {
            Schema::rename('devices_faults', 'devices_faults_events');
            DB::statement('ALTER TABLE devices_faults_events CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            Schema::table('devices_faults_events', function (Blueprint $table) {
                $table->string('fault_type', 64)->change();
            });
        } else {
            Schema::create('devices_faults_events', function (Blueprint $table) {
                $table->integer('fault_type_id')->index();
                $table->integer('iddevices')->primary();
                $table->string('fault_category', 24)->index();
                $table->string('fault_type', 64)->index();
                $table->timestamps();
                // these 2 columns were used on the open data dive only
                $table->string('fault_type_odd', 24)->index();
                $table->string('new_fault_type_odd', 24)->index();
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
            });
        }

        if (Schema::hasTable('devices_faults_opinions')) {
            if (Schema::hasColumn('devices_faults_opinions', 'age')) {
                Schema::table('devices_faults_opinions', function (Blueprint $table) {
                    $table->dropColumn('age');
                });
            }
            if (Schema::hasColumn('devices_faults_opinions', 'country')) {
                Schema::table('devices_faults_opinions', function (Blueprint $table) {
                    $table->dropColumn('country');
                });
            }
            DB::statement('ALTER TABLE devices_faults_opinions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
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

        Schema::create('devices_faults_adjudicated', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('iddevices')->index();
            $table->string('fault_type', 64)->index();
            $table->timestamps();
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->string('fault_type', 64)->index()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * DO NOT DROP `devices_faults` or `devices_faults_opinions`.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('devices_faults_adjudicated');

        Schema::rename('devices_faults_events', 'devices_faults');

        Schema::table('devices_faults', function (Blueprint $table) {
            $table->string('fault_type', 32)->change();
        });

        DB::statement('ALTER TABLE devices_faults CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci');
        DB::statement('ALTER TABLE devices_faults_opinions CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');

        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('fault_type');
        });
    }
};
