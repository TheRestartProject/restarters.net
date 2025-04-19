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
        $groups = \App\Group::join('grouptags_groups', 'groups.idgroups', '=', 'grouptags_groups.group')
            ->join('group_tags', 'grouptags_groups.group_tag', '=', 'group_tags.id')
            ->where('group_tags.id', \App\GroupTags::INACTIVE)
            ->get();

        foreach ($groups as $group) {
            $group->archived_at = $group->updated_at;

            // Remove [INACTIVE] from the group name - this is now indicated via archived_at.
            $group->name = str_replace('[INACTIVE] ', '', $group->name);
            $group->name = str_replace('[INACTIVE]', '', $group->name);
            $group->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // Add [INACTIVE] into all groups with archived_at.
        $groups = \App\Group::whereNotNull('archived_at')->get();

        foreach ($groups as $group) {
            $group->name = '[INACTIVE] ' . $group->name;
            $group->save();
        }
    }
};
