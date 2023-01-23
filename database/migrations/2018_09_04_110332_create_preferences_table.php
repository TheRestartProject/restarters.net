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
        Schema::create('preferences', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('purpose');
        });

        Schema::create('users_preferences', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->integer('preference_id')->unsigned();
            $table->foreign('preference_id')->references('id')->on('preferences');
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('preferences')->insert([
          'name' => 'Repair Directory',
          'purpose' => 'Show and hide the item in the main drop down menu',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users_preferences');
        Schema::dropIfExists('preferences');
    }
};
