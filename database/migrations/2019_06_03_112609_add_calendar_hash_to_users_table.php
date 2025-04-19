<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('calendar_hash')->after('drip_subscriber_id')->unique()->nullable();
        });

        $users = DB::table('users')
        ->whereNull('calendar_hash')
        ->select('id', 'calendar_hash')
        ->get();

        foreach ($users as $user) {
            $user = DB::table('users')
          ->where('id', $user->id)
          ->update(['calendar_hash' => Str::random(15)]);
            usleep(50000);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('calendar_hash');
        });
    }
};
