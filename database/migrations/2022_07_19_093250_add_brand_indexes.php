<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBrandIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
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
    public function down()
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropIndex('devices_brand_index');
        });
        Schema::table('brands', function (Blueprint $table) {
            $table->dropIndex('brands_brand_name_index');
        });
    }
}
