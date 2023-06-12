<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Group;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $countries = array_flip(App\Helpers\Fixometer::getAllCountries('en'));

        // Users has country column which is a code, except for ; check the contents and rename it.
        DB::update(DB::raw("UPDATE users SET country = 'GB' WHERE country = 'GBR'"));

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('country', 'country_code');
        });

        // Change the groups table to have country_code, not country.
        Schema::table('groups', function (Blueprint $table) {
            $table->string('country_code', 2)->after('area')->nullable();
        });

        $groups = DB::select(DB::raw('SELECT idgroups, country FROM groups'));

        foreach ($groups as $g) {
            // Countries are stored in English.
            if ($g->country) {
                $country = $g->country;
                $group = Group::findOrFail($g->idgroups);
                $group->country_code = $countries[$country];
                Log::debug('Country: ' . $group->country . ' => ' . $group->country_code);
                $group->save();
            }
        }

        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('country');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
