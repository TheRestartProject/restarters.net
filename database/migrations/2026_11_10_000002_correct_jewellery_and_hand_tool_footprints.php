<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // The 2021 Fixometer reference data (Summary tab) gives these categories' CO2e per item. The values loaded
    // in 2021 were the CO2e per kg instead: jewellery 59.19 (3.334 / 0.0563 kg), hand tool 4.67 (4.349 / 0.931 kg).
    private const JEWELLERY = 52;
    private const HAND_TOOL = 51;

    public function up(): void
    {
        DB::table('categories')->where('idcategories', self::JEWELLERY)->update(['footprint' => 3.334]);
        DB::table('categories')->where('idcategories', self::HAND_TOOL)->update(['footprint' => 4.349]);
    }

    public function down(): void
    {
        DB::table('categories')->where('idcategories', self::JEWELLERY)->update(['footprint' => 59.190]);
        DB::table('categories')->where('idcategories', self::HAND_TOOL)->update(['footprint' => 4.670]);
    }
};
