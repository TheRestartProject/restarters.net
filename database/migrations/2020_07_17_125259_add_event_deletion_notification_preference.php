<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEventDeletionNotificationPreference extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('preferences')->insert([
            'name' => 'Event deletion notification',
            'purpose' => NULL,
            'slug' => 'delete-event-notification',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $id = DB::table('preferences')->where('slug', 'delete-event-notification')->pluck('id');
        DB::table('users_preferences')->where('preference_id', $id)->delete();
        DB::table('preferences')->where('id', $id)->delete();
    }
}
