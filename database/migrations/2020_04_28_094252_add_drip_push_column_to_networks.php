<?php

use App\Network;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDripPushColumnToNetworks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('networks', function (Blueprint $table) {
            $table->boolean('users_push_to_drip')->nullable(false)->default(false)
                  ->after('include_in_zapier');
        });

        DB::table('networks')->where('id', 1)->update(['users_push_to_drip' => true]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('networks', function (Blueprint $table) {
            $table->dropColumn('users_push_to_drip');
        });
    }
}
