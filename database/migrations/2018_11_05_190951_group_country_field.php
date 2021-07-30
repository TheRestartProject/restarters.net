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
            $geocode = new \App\Helpers\Geocoder();
            $geocoded = $geocode->geocode("$group->latitude, $group->longitude");

            if (! empty($geocoded)) {
                $update = DB::table('groups')->where('idgroups', $group->idgroups)->update([
              'country' => $geocoded['country'],
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
