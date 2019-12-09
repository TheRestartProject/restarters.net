<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUpdatedAtToDevicesFaultsOpinionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('devices_faults_opinions', function (Blueprint $table) {
          $table->datetime('updated_at')->after('session_id');
        });        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('devices_faults_opinions', function (Blueprint $table) {
          $table->dropColumn('updated_at');
        });
    }
}
