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
        DB::table('roles')->insert([
            'role' => 'NetworkCoordinator',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::delete("delete from roles where role = 'NetworkCoordinator'");
    }
};
