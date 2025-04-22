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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('drip_subscriber_id');
        });

        Schema::table('networks', function (Blueprint $table) {
            $table->dropColumn('users_push_to_drip');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('drip_subscriber_id')->nullable()->after('newsletter')->unique();
        });

        Schema::table('networks', function (Blueprint $table) {
            $table->boolean('users_push_to_drip')->nullable(false)->default(false);
        });
    }
};
