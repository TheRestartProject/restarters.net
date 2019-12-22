<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAgeCountryToDevicesFaultsOpinionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('devices_faults_opinions', function (Blueprint $table) {
          $table->string('age', 8);
          $table->string('country', 8);
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
          $table->dropColumn('age');
          $table->dropColumn('country');
        });
    }
}
