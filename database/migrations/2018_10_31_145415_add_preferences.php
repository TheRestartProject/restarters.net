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
    public function up(): void
    {
        // Additional preferences
        DB::table('preferences')->insert([
          'name' => 'Admin New User',
          'purpose' => null,
          'slug' => 'admin-new-user',
        ]);
        DB::table('preferences')->insert([
          'name' => 'Admin Moderate Event',
          'purpose' => null,
          'slug' => 'admin-moderate-event',
        ]);
        DB::table('preferences')->insert([
          'name' => 'Admin Moderate Group',
          'purpose' => null,
          'slug' => 'admin-moderate-group',
        ]);
        DB::table('preferences')->insert([
          'name' => 'Admin Abnormal Devices',
          'purpose' => null,
          'slug' => 'admin-abnormal-devices',
        ]);
        DB::table('preferences')->insert([
          'name' => 'Admin Approve WordPress Event Failure',
          'purpose' => null,
          'slug' => 'admin-approve-wordpress-event-failure',
        ]);
        DB::table('preferences')->insert([
          'name' => 'Admin Edit WordPress Event Failure',
          'purpose' => null,
          'slug' => 'admin-edit-wordpress-event-failure',
        ]);
        DB::table('preferences')->insert([
          'name' => 'Admin Approve WordPress Group Failure',
          'purpose' => null,
          'slug' => 'admin-approve-wordpress-group-failure',
        ]);
        DB::table('preferences')->insert([
          'name' => 'Admin Edit WordPress Group Failure',
          'purpose' => null,
          'slug' => 'admin-edit-wordpress-group-failure',
        ]);
        DB::table('preferences')->insert([
          'name' => 'Admin No Devices',
          'purpose' => null,
          'slug' => 'admin-no-devices',
        ]);

        // Additional permission
        DB::table('permissions')->insert([
          'permission' => 'Verify Translation Access',
          'purpose' => null,
          'slug' => 'verify-translation-access',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        DB::table('users_preferences')->delete();
        DB::table('preferences')->truncate();
    }
};
