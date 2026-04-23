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
        Schema::table('events_users', function (Blueprint $table) {
            $table->string('full_name')->nullable();
            $table->integer('user')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events_users', function (Blueprint $table) {
            $table->dropColumn('full_name');
            $table->integer('user')->nullable(false)->change();
        });
    }
};
