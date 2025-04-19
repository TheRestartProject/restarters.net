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
        DB::statement('ALTER TABLE `users`
                         ADD COLUMN `recovery` VARCHAR(45) NULL AFTER `role`,
                         ADD COLUMN `recovery_expires` TIMESTAMP NULL AFTER `recovery`;'
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        //
    }
};
