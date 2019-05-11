<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueShareableCodeToEventsAndGroups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('shareable_code')->after('free_text')->nullable();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->string('shareable_code')->after('free_text')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('shareable_code');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('shareable_code');
        });
    }
}
