<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdminModerateEventPhotosPreference extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('preferences')->insert([
            'name' => 'Admin Moderate Event Photos',
            'purpose' => null,
            'slug' => 'admin-moderate-event-photos',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $id = DB::table('preferences')->where('slug', 'admin-moderate-event-photos')->pluck('id');
        DB::table('users_preferences')->where('preference_id', $id)->delete();
        DB::table('preferences')->where('id', $id)->delete();
    }
}
