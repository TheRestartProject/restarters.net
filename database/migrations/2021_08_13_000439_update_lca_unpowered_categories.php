<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
SELECT
c1.idcategories,
c1.name,
c1.weight as weight_new,
c2.weight as weight_old,
c1.footprint as footprint_new,
c2.footprint as footprint_old
FROM `restarters_db_test`.categories c1
LEFT JOIN `restarters.test`.categories c2 ON c2.idcategories = c1.idcategories
UNION
SELECT
c1.idcategories,
c1.name,
c1.weight as weight_new,
c2.weight as weight_old,
c1.footprint as footprint_new,
c2.footprint as footprint_old
FROM `restarters_db_test`.categories c1
RIGHT JOIN `restarters.test`.categories c2 ON c2.idcategories = c1.idcategories
     */
    public function up(): void
    {
        DB::statement('SET foreign_key_checks=0');
        DB::table('categories')->insert([
            'idcategories' => 6,
            'name' => 'Games console',
            'powered' => 1,
            'cluster' => 3,
            'aggregate' => 0,
        ]);
        DB::table('categories')->insert([
            'idcategories' => 7,
            'name' => 'Watch/clock',
            'powered' => 1,
            'cluster' => 4,
            'aggregate' => 0,
        ]);
        DB::table('categories')->insert([
            'idcategories' => 8,
            'name' => 'Sewing machine',
            'powered' => 1,
            'cluster' => 4,
            'aggregate' => 0,
        ]);
        DB::table('categories')->insert([
            'idcategories' => 9,
            'name' => 'Iron',
            'powered' => 1,
            'cluster' => 4,
            'aggregate' => 0,
        ]);
        DB::table('categories')->insert([
            'idcategories' => 10,
            'name' => 'Coffee maker',
            'powered' => 1,
            'cluster' => 4,
            'aggregate' => 0,
        ]);
        DB::table('categories')->insert([
            'idcategories' => 51,
            'name' => 'Hand tool',
            'powered' => 0,
            'cluster' => 5,
            'aggregate' => 0,
        ]);
        DB::table('categories')->insert([
            'idcategories' => 52,
            'name' => 'Jewellery',
            'powered' => 0,
            'cluster' => 5,
            'aggregate' => 0,
        ]);
        DB::table('category_revisions')->insert([
            'idcategory_revisions' => 2,
            'revision' => 'Second Revision',
            'created_at' => now(),
        ]);
        DB::statement('SET foreign_key_checks=1');

        $cats = [
            ['idcategories' => '6', 'weight' => '3.220', 'footprint' => '140.670'],
            ['idcategories' => '7', 'weight' => '1.300', 'footprint' => '28.290'],
            ['idcategories' => '8', 'weight' => '8.600', 'footprint' => '35.000'],
            ['idcategories' => '9', 'weight' => '1.170', 'footprint' => '11.000'],
            ['idcategories' => '10', 'weight' => '1.280', 'footprint' => '17.500'],
            ['idcategories' => '11', 'weight' => '6.690', 'footprint' => '414.170'],
            ['idcategories' => '12', 'weight' => '3.150', 'footprint' => '262.560'],
            ['idcategories' => '13', 'weight' => '3.660', 'footprint' => '304.880'],
            ['idcategories' => '14', 'weight' => '5.230', 'footprint' => '343.490'],
            ['idcategories' => '15', 'weight' => '2.530', 'footprint' => '414.700'],
            ['idcategories' => '16', 'weight' => '1.830', 'footprint' => '265.320'],
            ['idcategories' => '17', 'weight' => '1.390', 'footprint' => '221.150'],
            ['idcategories' => '18', 'weight' => '3.800', 'footprint' => '59.500'],
            ['idcategories' => '19', 'weight' => '0.440', 'footprint' => '15.940'],
            ['idcategories' => '20', 'weight' => '12.470', 'footprint' => '100.690'],
            ['idcategories' => '21', 'weight' => '0.480', 'footprint' => '29.730'],
            ['idcategories' => '22', 'weight' => '0.590', 'footprint' => '16.030'],
            ['idcategories' => '23', 'weight' => '0.140', 'footprint' => '22.710'],
            ['idcategories' => '24', 'weight' => '0.400', 'footprint' => '3.000'],
            ['idcategories' => '25', 'weight' => '0.150', 'footprint' => '50.510'],
            ['idcategories' => '26', 'weight' => '0.770', 'footprint' => '114.490'],
            ['idcategories' => '27', 'weight' => '10.600', 'footprint' => '284.250'],
            ['idcategories' => '28', 'weight' => '18.700', 'footprint' => '349.580'],
            ['idcategories' => '29', 'weight' => '8.950', 'footprint' => '186.500'],
            ['idcategories' => '30', 'weight' => '8.720', 'footprint' => '123.860'],
            ['idcategories' => '31', 'weight' => '17.700', 'footprint' => '109.500'],
            ['idcategories' => '32', 'weight' => '2.060', 'footprint' => '54.650'],
            ['idcategories' => '33', 'weight' => '2.680', 'footprint' => '57.580'],
            ['idcategories' => '34', 'weight' => '1.990', 'footprint' => '51.330'],
            ['idcategories' => '35', 'weight' => '7.600', 'footprint' => '99.410'],
            ['idcategories' => '36', 'weight' => '0.460', 'footprint' => '14.720'],
            ['idcategories' => '37', 'weight' => '3.900', 'footprint' => '15.600'],
            ['idcategories' => '38', 'weight' => '0.670', 'footprint' => '10.280'],
            ['idcategories' => '39', 'weight' => '1.250', 'footprint' => '44.320'],
            ['idcategories' => '40', 'weight' => '2.680', 'footprint' => '14.960'],
            ['idcategories' => '41', 'weight' => '1.650', 'footprint' => '26.650'],
            ['idcategories' => '42', 'weight' => '2.950', 'footprint' => '30.900'],
            ['idcategories' => '43', 'weight' => '1.470', 'footprint' => '8.130'],
            ['idcategories' => '44', 'weight' => '0.930', 'footprint' => '10.400'],
            ['idcategories' => '45', 'weight' => '6.800', 'footprint' => '36.010'],
            ['idcategories' => '46', 'weight' => '0', 'footprint' => '0'],
            ['idcategories' => '47', 'weight' => '29.810', 'footprint' => '67.130'],
            ['idcategories' => '48', 'weight' => '15.100', 'footprint' => '149.600'],
            ['idcategories' => '49', 'weight' => '0.750', 'footprint' => '20.320'],
            ['idcategories' => '50', 'weight' => '0', 'footprint' => '0'],
            ['idcategories' => '51', 'weight' => '0.930', 'footprint' => '4.670'],
            ['idcategories' => '52', 'weight' => '0.060', 'footprint' => '59.190'],
        ];

        foreach ($cats as $cat) {
            DB::table('categories')->where('idcategories', $cat['idcategories'])->update([
                'weight' => $cat['weight'],
                'footprint' => $cat['footprint'],
            ]);
        }

        DB::table('categories')->where('idcategories', 50)->update([
            'aggregate' => 1,
        ]);

        DB::statement('UPDATE categories SET revision=2');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET foreign_key_checks=0');
        DB::table('categories')->where('idcategories', 6)->delete();
        DB::table('categories')->where('idcategories', 7)->delete();
        DB::table('categories')->where('idcategories', 8)->delete();
        DB::table('categories')->where('idcategories', 9)->delete();
        DB::table('categories')->where('idcategories', 10)->delete();
        DB::table('categories')->where('idcategories', 51)->delete();
        DB::table('categories')->where('idcategories', 52)->delete();
        DB::table('category_revisions')->where('idcategory_revisions', 2)->delete();
        DB::statement('SET foreign_key_checks=1');

        $cats = [
            ['idcategories' => '11', 'weight' => '9.150', 'footprint' => '398.4'],
            ['idcategories' => '12', 'weight' => '2.700', 'footprint' => '72.4'],
            ['idcategories' => '13', 'weight' => '3.720', 'footprint' => '102.93'],
            ['idcategories' => '14', 'weight' => '5.000', 'footprint' => '167.8'],
            ['idcategories' => '15', 'weight' => '2.755', 'footprint' => '322.79'],
            ['idcategories' => '16', 'weight' => '2.260', 'footprint' => '258.25'],
            ['idcategories' => '17', 'weight' => '2.140', 'footprint' => '142.18'],
            ['idcategories' => '18', 'weight' => '7.000', 'footprint' => '47.7'],
            ['idcategories' => '19', 'weight' => '1.185', 'footprint' => '18.87'],
            ['idcategories' => '20', 'weight' => '7.050', 'footprint' => '47.7'],
            ['idcategories' => '21', 'weight' => '0.113', 'footprint' => '6.13'],
            ['idcategories' => '22', 'weight' => '0.270', 'footprint' => '4.05'],
            ['idcategories' => '23', 'weight' => '0.149', 'footprint' => '13'],
            ['idcategories' => '24', 'weight' => '0.260', 'footprint' => '4.05'],
            ['idcategories' => '25', 'weight' => '0.140', 'footprint' => '35.82'],
            ['idcategories' => '26', 'weight' => '0.510', 'footprint' => '107.76'],
            ['idcategories' => '27', 'weight' => '10.600', 'footprint' => '284.25'],
            ['idcategories' => '28', 'weight' => '18.700', 'footprint' => '359.4'],
            ['idcategories' => '29', 'weight' => '10.900', 'footprint' => '109.5'],
            ['idcategories' => '30', 'weight' => '10.900', 'footprint' => '109.5'],
            ['idcategories' => '31', 'weight' => '10.900', 'footprint' => '109.5'],
            ['idcategories' => '32', 'weight' => '2.500', 'footprint' => '66'],
            ['idcategories' => '33', 'weight' => '2.350', 'footprint' => '46'],
            ['idcategories' => '34', 'weight' => '1.000', 'footprint' => '25'],
            ['idcategories' => '35', 'weight' => '18.500', 'footprint' => '109.53'],
            ['idcategories' => '36', 'weight' => '0.015', 'footprint' => '13.43'],
            ['idcategories' => '37', 'weight' => '0.880', 'footprint' => '4.52'],
            ['idcategories' => '38', 'weight' => '0.690', 'footprint' => '6'],
            ['idcategories' => '39', 'weight' => '1.400', 'footprint' => '17.1'],
            ['idcategories' => '40', 'weight' => '0.703', 'footprint' => '4.62'],
            ['idcategories' => '41', 'weight' => '2.840', 'footprint' => '26.6'],
            ['idcategories' => '42', 'weight' => '2.700', 'footprint' => '15.8'],
            ['idcategories' => '43', 'weight' => '1.040', 'footprint' => '5'],
            ['idcategories' => '44', 'weight' => '1.270', 'footprint' => '15'],
            ['idcategories' => '45', 'weight' => '7.780', 'footprint' => '41'],
            ['idcategories' => '46', 'weight' => '1.00', 'footprint' => null],
            ['idcategories' => '47', 'weight' => null, 'footprint' => null],
            ['idcategories' => '48', 'weight' => null, 'footprint' => null],
            ['idcategories' => '49', 'weight' => null, 'footprint' => null],
            ['idcategories' => '50', 'weight' => null, 'footprint' => null],
        ];

        foreach ($cats as $cat) {
            DB::table('categories')->where('idcategories', $cat['idcategories'])->update([
                'weight' => $cat['weight'],
                'footprint' => $cat['footprint'],
            ]);
        }

        DB::table('categories')->where('idcategories', 50)->update([
            'aggregate' => 0,
        ]);

        DB::statement('UPDATE categories SET revision=1');
    }
};
