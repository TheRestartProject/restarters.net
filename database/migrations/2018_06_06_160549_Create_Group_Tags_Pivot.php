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
        Schema::create('grouptags_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('group_tag');
            $table->string('group');
        });
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('tags');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grouptags_groups');
        Schema::table('groups', function (Blueprint $table) {
            $table->string('tags');
        });
    }
};
