<?php

use Illuminate\Database\Migrations\Migration;

class UpdateLcaUnpoweredCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('categories')->where('idcategories', 47)->update([
            'weight' => 29.81,
            'footprint' => 67.13,
        ]);
        DB::table('categories')->where('idcategories', 48)->update([
            'weight' => 15.10,
            'footprint' => 149.6,
        ]);
        DB::table('categories')->where('idcategories', 49)->update([
            'weight' => 0.75,
            'footprint' => 20.32,
        ]);
        DB::table('categories')->where('idcategories', 50)->update([
            'weight' => 0,
            'footprint' => 0,
        ]);
        DB::table('categories')->where('idcategories', 46)->update([
            'weight' => 0,
            'footprint' => 0,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('categories')->where('idcategories', 47)->update([
            'weight' => NULL,
            'footprint' => NULL,
        ]);
        DB::table('categories')->where('idcategories', 48)->update([
            'weight' => NULL,
            'footprint' => NULL,
        ]);
        DB::table('categories')->where('idcategories', 49)->update([
            'weight' => NULL,
            'footprint' => NULL,
        ]);
        DB::table('categories')->where('idcategories', 50)->update([
            'weight' => NULL,
            'footprint' => NULL,
        ]);
        DB::table('categories')->where('idcategories', 46)->update([
            'weight' => 1,
            'footprint' => NULL,
        ]);
    }
}
