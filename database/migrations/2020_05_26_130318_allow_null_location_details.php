<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AllowNullLocationDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('location', 255)->nullable()->change();
            $table->float('latitude')->nullable()->change();
            $table->float('longitude')->nullable()->change();
            //
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('location', 255)->nullable(false)->change();
            $table->float('latitude')->nullable(false)->change();
            $table->float('longitude')->nullable(false)->change();
        });
    }
}
