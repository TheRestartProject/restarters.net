<?php

use App\Helpers\Fixometer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Groups created since country_code was introduced only had `country` filled in by the hourly
        // groups:country job, and recent groups were found with it empty.  The Group model now sets it on
        // save; backfill any existing gaps here.  Query builder rather than models so no audit rows or
        // update listeners (WordPress/Discourse sync) fire.  Countries are stored in English.
        $countries = Fixometer::getAllCountries('en');

        $groups = DB::table('groups')
            ->whereNotNull('country_code')
            ->where(function ($q) {
                $q->whereNull('country')->orWhere('country', '');
            })
            ->get(['idgroups', 'country_code']);

        foreach ($groups as $group) {
            if (isset($countries[$group->country_code])) {
                DB::table('groups')
                    ->where('idgroups', $group->idgroups)
                    ->update(['country' => $countries[$group->country_code]]);
            }
        }
    }

    public function down(): void
    {
        // Data-only backfill; nothing to undo.
    }
};
