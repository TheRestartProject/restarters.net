<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPreferences extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('preferences')->insert([
          'name' => 'Admin New User',
          'purpose' => NULL,
          'slug' => 'admin-new-user',
        ]);
        DB::table('preferences')->insert([
          'name' => 'Admin Moderate Event',
          'purpose' => NULL,
          'slug' => 'admin-moderate-event',
        ]);
        DB::table('preferences')->insert([
          'name' => 'Admin Moderate Group',
          'purpose' => NULL,
          'slug' => 'admin-moderate-group',
        ]);
        DB::table('preferences')->insert([
          'name' => 'Admin Abnormal Devices',
          'purpose' => NULL,
          'slug' => 'admin-abnormal-devices',
        ]);
        DB::table('preferences')->insert([
          'name' => 'Verify Translation Access',
          'purpose' => NULL,
          'slug' => 'verify-translation-access',
        ]);
        DB::table('preferences')->insert([
          'name' => 'Admin Approve WordPress Event Failure',
          'purpose' => NULL,
          'slug' => 'admin-approve-wordpress-event-failure',
        ]);
        DB::table('preferences')->insert([
          'name' => 'Admin Edit WordPress Event Failure',
          'purpose' => NULL,
          'slug' => 'admin-edit-wordpress-event-failure',
        ]);
        DB::table('preferences')->insert([
          'name' => 'Admin Approve WordPress Group Failure',
          'purpose' => NULL,
          'slug' => 'admin-approve-wordpress-group-failure',
        ]);
        DB::table('preferences')->insert([
          'name' => 'Admin Edit WordPress Group Failure',
          'purpose' => NULL,
          'slug' => 'admin-edit-wordpress-group-failure',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('users_preferences')->delete();
        DB::table('preferences')->truncate();
    }
}
