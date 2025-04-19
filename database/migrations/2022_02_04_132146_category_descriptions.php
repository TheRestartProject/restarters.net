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
        Schema::table('categories', function (Blueprint $table) {
            $table->string('description_short', 255);
            $table->text('description_long');
        });

        $descs = [
            35 => 'Home/office appliance that adjusts ambient air quality.',
            10 => 'Powered filter or espresso machine, Nespresso etc.',
            36 => 'Bike lights, night lights, torches, fairy lights etc.',
            11 => 'Tower, mini tower, midi tower, desktop, all-in-one, unibody.',
            21 => 'Smaller electronic cameras.',
            22 => 'Larger electronic cameras.',
            37 => 'Cooling fan, fan heater.',
            12 => 'Smaller TVs and monitors.',
            13 => 'TVs and monitors.',
            14 => 'TVs and monitors.',
            27 => 'TVs and monitors.',
            28 => 'Larger TVs and monitors.',
            6 => 'Games boxes e.g. Playstation, XBox. Note that a small console may be classified as a “Hand-held entertainment device”.',
            38 => 'Hair straighteners, hair dryers, toothbrushes, shavers etc.',
            23 => 'iPods, Walkmans, handheld audio players, portable game consoles (e.g. Nintendo Switch, PSP, gameboy).',
            24 => 'Earbuds, bluetooth buds, on-ear and over-ear cups and headsets.',
            29 => 'Mini-stereos and "Boom Boxes".',
            30 => 'Any component of a stereo system.',
            9 => 'Steam or non-steam clothes iron, travel iron.',
            39 => 'Powered appliance for boiling water.',
            40 => 'Desk lamp, floor lamp, wall lamp.',
            15 => 'Any laptop larger than 15” - mostly for graphics and gaming.',
            16 => 'Most laptops.',
            17 => 'Netbooks or ultrabooks.',
            46 => 'Any powered device that does not fit in another category.',
            25 => 'Any hand-held smartphone or other telecommunications device.',
            31 => 'Any powered instrument e.g. keyboard, guitar.',
            18 => 'Home/office appliance for shredding documents.',
            19 => 'Mice, keyboards, webcams, laptop chargers etc.',
            32 => 'Transistor, digital, clock/radio devices etc.',
            41 => 'Powered DIY and garden tools and devices, e.g. jigsaw, leafblower.',
            20 => 'Any inkjet, laserjet, scanner, copier or combination appliance.',
            33 => 'Slide projector, video projector, digital projector, home theatre.',
            8 => 'Powered appliance for stitching fabric including overlocker.',
            42 => 'Blenders, grinders, food processors, bread makers etc.',
            26 => 'Any screen over 6” including Kindles and satnavs.',
            43 => 'Kitchen appliance for browning baked goods.',
            44 => 'Any mains or battery powered toy.',
            34 => 'Setup boxes, DVD players, game controllers etc.',
            45 => 'Includes hand-held vacuums and steam mop.',
            7 => 'Any electronic time-keeping or fitness monitoring device.',
            48 => 'Entire bicycle. Note that bicycle accessories may fit into another category, e.g. bike lights.',
            49 => 'Clothes, soft-furnishings or other fabric/textile items.',
            47 => 'Larger household items such as chairs and tables.',
            51 => 'Non-electrical tools such as saws, hammers, secateurs and spades.',
            52 => 'Small, personal ornaments, such as earrings, necklaces and bracelets, also spectacles.',
            50 => 'Items that do not require power and that don’t fit into another category.',
        ];

        foreach ($descs as $k => $v) {
            DB::table('categories')->where('idcategories', $k)->update([
                'description_short' => $v,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('description_short');
            $table->dropColumn('description_long');
        });
    }
};
