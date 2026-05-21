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
        Schema::table('group_tags', function (Blueprint $table) {
            // Add network_id column - NULL means global tag (admin-only)
            $table->unsignedInteger('network_id')->nullable()->after('id');
            $table->foreign('network_id')->references('id')->on('networks')->onDelete('cascade');

            // Tag names must be unique within a network (or among global tags)
            $table->unique(['tag_name', 'network_id'], 'group_tags_tag_name_network_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_tags', function (Blueprint $table) {
            $table->dropUnique('group_tags_tag_name_network_unique');
            $table->dropForeign(['network_id']);
            $table->dropColumn('network_id');
        });
    }
};
