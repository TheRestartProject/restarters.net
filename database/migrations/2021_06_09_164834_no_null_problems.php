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
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('problem');
        });
        Schema::table('devices', function (Blueprint $table) {
            $table->mediumText('problem');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('problem');
        });
        Schema::table('devices', function (Blueprint $table) {
            $table->mediumText('problem')->nullable();
        });
    }
};
