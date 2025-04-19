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
        DB::table('preferences')->insert([
            'name' => 'Event deletion notification',
            'purpose' => null,
            'slug' => 'delete-event-notification',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        $id = DB::table('preferences')->where('slug', 'delete-event-notification')->pluck('id');
        DB::table('users_preferences')->where('preference_id', $id)->delete();
        DB::table('preferences')->where('id', $id)->delete();
    }
};
