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
    public function up(): void
    {
        $countries = array_flip(App\Helpers\Fixometer::getAllCountries('en'));

        // Users has country column which is a code, except for GBR; fix the contents and rename it.
        DB::update(DB::raw("UPDATE users SET country = 'GB' WHERE country = 'GBR'"));

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('country', 'country_code');
        });

        // Add country code to groups table - we leave the country field in place because it's used by
        // e.g. ORA exports.  There is a scheduled job which updates the country field from the
        // code.
        Schema::table('groups', function (Blueprint $table) {
            $table->string('country_code', 2)->after('area')->nullable();
        });

        $groups = DB::select(DB::raw('SELECT idgroups, country FROM `groups`'));

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
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('country_code');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('country_code', 'country');
        });

        DB::update(DB::raw("UPDATE users SET country = 'GBR' WHERE country = 'GB'"));
    }
};
