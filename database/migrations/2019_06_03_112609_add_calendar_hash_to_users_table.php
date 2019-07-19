<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCalendarHashToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
          $table->string('calendar_hash')->after('drip_subscriber_id')->unique()->nullable();
        });

        $users = DB::table('users')
        ->whereNull('calendar_hash')
        ->select('id','calendar_hash')
        ->get();

        foreach($users as $user) {
          $user = DB::table('users')
          ->where('id', $user->id)
          ->update(['calendar_hash' => str_random(15)]);
          usleep(50000);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
          $table->dropColumn('calendar_hash');
        });
    }
}
