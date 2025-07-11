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
        // Using raw SQL as tinyint columns can't be altered.
        // See https://laravel.com/docs/5.6/migrations#modifying-columns
        DB::statement("ALTER TABLE `users_groups` CHANGE `role` `role` TINYINT(4) NOT NULL DEFAULT '4'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `users_groups` CHANGE `role` `role` TINYINT(4) NOT NULL DEFAULT '3'");
    }
};
