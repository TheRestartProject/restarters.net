<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class VolunteerEventUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events_users', function (Blueprint $table) {
            $table->string('full_name')->nullable();
            $table->integer('user')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('events_users', function (Blueprint $table) {
            $table->dropColumn('full_name');
            $table->integer('user')->nullable(false)->change();
        });
    }
}
