<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $cats = [
            19 => 'PC accessory',
            21 => 'Digital compact camera',
            22 => 'DSLR/video camera',
            35 => 'Aircon/dehumidifier',
            38 => 'Hair & beauty item',
        ];
        foreach ($cats as $k => $v) {
            DB::table('categories')->where('idcategories', $k)->update([
                'name' => $v,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $cats = [
            19 => 'PC Accessory',
            21 => 'Digital Compact Camera',
            22 => 'DLSR / Video Camera',
            35 => 'Aircon/Dehumidifier',
            38 => 'Hair & Beauty item',
        ];
        foreach ($cats as $k => $v) {
            DB::table('categories')->where('idcategories', $k)->update([
                'name' => $v,
            ]);
        }
    }
};
