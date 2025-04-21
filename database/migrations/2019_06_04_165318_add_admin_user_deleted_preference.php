<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('preferences')->insert([
            'name' => 'Admin User Deleted',
            'purpose' => null,
            'slug' => 'admin-user-deleted',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $id = DB::table('preferences')->where('slug', 'admin-user-deleted')->pluck('id');
        DB::table('users_preferences')->where('preference_id', $id)->delete();
        DB::table('preferences')->where('id', $id)->delete();
    }
};
