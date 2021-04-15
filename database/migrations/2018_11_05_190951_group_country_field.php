<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GroupCountryField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('country')->after('area')->nullable();
        });

        $groups = DB::table('groups')
                      ->whereNotNull('latitude')
                        ->whereNotNull('longitude')
                          ->get();

        foreach ($groups as $group) {
            $lat_long = FixometerHelper::getLatLongFromCityCountry($group->latitude, $group->longitude);

            if (isset($lat_long[2]) && ! empty($lat_long[2])) {
                $update = DB::table('groups')->where('idgroups', $group->idgroups)->update([
                    'country' => $lat_long[2],
                ]);

                usleep(250000);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('country');
        });
    }
}
