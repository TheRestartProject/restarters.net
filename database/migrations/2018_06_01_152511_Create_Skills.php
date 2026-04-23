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
        Schema::create('skills', function (Blueprint $table) {
            $table->increments('id');
            $table->string('skill_name');
            $table->string('description');
            $table->timestamps();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('skills');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skills');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('skills');
        });
    }
};
