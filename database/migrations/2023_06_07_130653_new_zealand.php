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
        DB::statement(DB::raw("update `groups` set country = 'Aotearoa New Zealand' where country = 'New Zealand';"));
        DB::statement(DB::raw("update `groups` set country = 'Belgique' where country = 'Belgium';"));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement(DB::raw("update `groups` set country = 'New Zealand' where country = 'Aotearoa New Zealand';"));
        DB::statement(DB::raw("update `groups` set country = 'Belgium' where country = 'Belgique';"));
    }
};
