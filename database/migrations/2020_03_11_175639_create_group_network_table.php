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
        Schema::create('group_network', function (Blueprint $table) {
            $table->primary(['group_id', 'network_id']);
            $table->unsignedInteger('group_id')->unsigned()->nullable(false);
            $table->unsignedInteger('network_id')->unsigned()->nullable(false);
            //$table->foreign('group_id')->references('idgroups')->on('groups');
            //$table->foreign('network_id')->references('id')->on('networks');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('group_network');
    }
};
