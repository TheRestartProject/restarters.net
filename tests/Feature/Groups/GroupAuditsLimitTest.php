<?php

namespace Tests\Feature\Groups;

use App\Group;
use App\User;
use Tests\TestCase;

class GroupAuditsLimitTest extends TestCase
{
    /**
     * A busy group's whole history was too much for the edit page's Group log, so, as the legacy page did, only
     * the latest 100 changes are sent.
     *
     * @story:App\Http\Controllers\API\GroupController::auditsv2
     */
    public function testOnlyTheLatestHundredChangesAreSent(): void
    {
        $group = Group::factory()->create(['approved' => true]);

        for ($i = 1; $i <= 105; $i++) {
            $group->free_text = "Change $i";
            $group->save();
        }

        $this->actingAs(User::factory()->administrator()->create());

        $response = $this->getJson('/api/v2/groups/'.$group->idgroups.'/audits');

        $response->assertStatus(200);
        $audits = $response->json('data');
        $this->assertCount(100, $audits);
        $this->assertStringContainsString('Change 105', json_encode($audits[0]));
    }
}
