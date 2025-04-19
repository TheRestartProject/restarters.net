<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
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
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('country');
        });
    }
};
