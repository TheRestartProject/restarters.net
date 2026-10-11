<?php

use App\Group;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        // Groups created since country_code was introduced only had `country` filled in by the hourly
        // groups:country job, and recent groups were found with it empty.  The Group model now sets it on
        // save; backfill any existing gaps here.  Query builder rather than models so no audit rows or
        // update listeners (WordPress/Discourse sync) fire.  Countries are stored in English.
        $groups = DB::table('groups')
            ->whereNotNull('country_code')
            ->where(function ($q) {
                $q->whereNull('country')->orWhere('country', '');
            })
            ->get(['idgroups', 'country_code']);

        foreach ($groups as $group) {
            $country = Group::countryNameForCode($group->country_code);

            if ($country !== '') {
                DB::table('groups')->where('idgroups', $group->idgroups)->update(['country' => $country]);
            } else {
                Log::warning("Group {$group->idgroups}: no country name for code '{$group->country_code}'");
            }
        }
    }

    public function down(): void
    {
        // Data-only backfill; nothing to undo.
    }
};
