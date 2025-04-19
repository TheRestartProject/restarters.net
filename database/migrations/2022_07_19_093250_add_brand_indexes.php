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
        Schema::table('brands', function (Blueprint $table) {
            $table->index('brand_name');
        });
        Schema::table('devices', function (Blueprint $table) {
            $table->index('brand');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropIndex('devices_brand_index');
        });
        Schema::table('brands', function (Blueprint $table) {
            $table->dropIndex('brands_brand_name_index');
        });
    }
};
