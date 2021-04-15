<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupTags extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_tags', function (Blueprint $table) {
            $table->increments('id');
            $table->string('tag_name');
            $table->string('description');
            $table->timestamps();
        });
        Schema::table('groups', function (Blueprint $table) {
            $table->string('tags');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('group_tags');
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('tags');
        });
    }
}
