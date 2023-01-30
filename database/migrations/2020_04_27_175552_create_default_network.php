<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        // This is the default 'Restarters' network - that which
        // groups will be a member of by default, if not part of
        // another network.
        DB::table('networks')->insert([
            'id' => 1,
            'name' => 'Restarters',
            'description' => 'The global Restarters network.  Groups are a member of this network by default, if not created as part of another network.',
            'website' => 'https://therestartproject.org',
            'default_language' => 'en',
            'timezone' => 'Europe/London',
            'events_push_to_wordpress' => true,
            'include_in_zapier' => true,
            'shortname' => 'restarters',
            'created_at' => Carbon::now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('networks')->where('id', 1)->delete();
    }
};
