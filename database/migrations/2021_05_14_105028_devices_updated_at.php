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
        Schema::table('events', function (Blueprint $table) {
            $table->datetime('devices_updated_at')->nullable();
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->datetime('devices_updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('devices_updated_at');
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('devices_updated_at');
        });
    }
};
