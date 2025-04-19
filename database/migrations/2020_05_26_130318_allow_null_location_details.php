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
        Schema::table('events', function (Blueprint $table) {
            $table->string('location', 255)->nullable()->change();
            $table->float('latitude')->nullable()->change();
            $table->float('longitude')->nullable()->change();
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('location', 255)->nullable(false)->change();
            $table->float('latitude')->nullable(false)->change();
            $table->float('longitude')->nullable(false)->change();
        });
    }
};
