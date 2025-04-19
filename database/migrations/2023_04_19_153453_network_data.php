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
        // Add JSON columns.  In older versions of MySQL these will be LONGTEXT rather than JSON, so
        // if we ever want to do extraction/query/indexing on them we might need to migrate them at that point.
        Schema::table('groups', function (Blueprint $table) {
            $table->json('network_data')->nullable();
        });
        Schema::table('events', function (Blueprint $table) {
            $table->json('network_data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('network_data');
        });
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('network_data');
        });
    }
};
