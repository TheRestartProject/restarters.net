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
        // Make networks table unique on name
        Schema::table('networks', function (Blueprint $table) {
            $table->unique('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore networks table to be non-unique
        Schema::table('networks', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
    }
};
