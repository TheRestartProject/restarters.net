<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class BrandList extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('brands')->truncate();

        if (($handle = fopen(base_path().'/csv/brands.csv', 'r')) !== false) {
            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                DB::table('brands')->insert([
              'brand_name' => $row[0],
            ]);
            }
        }
    }
}
