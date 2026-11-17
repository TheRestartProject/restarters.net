<?php

namespace Tests\Feature\Events;

use App\Group;
use App\Party;
use App\User;
use Tests\TestCase;

class EventAuditUserTest extends TestCase
{
    /**
     * The client sends a Sanctum token rather than a session cookie. The audit trail must still record who made
     * the change, or the Event log reads ":user_name updated record".
     *
     * @story:App\Http\Controllers\API\EventController::auditsv2
     */
    public function testChangesMadeWithATokenRecordWhoMadeThem(): void
    {
        $group = Group::factory()->create();
        $event = Party::factory()->create(['group' => $group->idgroups, 'link' => 'https://example.org/old']);
        $admin = User::factory()->administrator()->create(['name' => 'Audit Admin']);
        $headers = ['Authorization' => 'Bearer '.$admin->createToken('spa')->plainTextToken, 'Accept' => 'application/json'];

        $this->patch('/api/v2/events/'.$event->idevents, ['link' => 'https://example.org/new'], $headers)
            ->assertSuccessful();

        $audit = $event->audits()->latest('id')->first();
        $this->assertNotNull($audit);
        $this->assertEquals($admin->id, $audit->user_id);

        $response = $this->getJson('/api/v2/events/'.$event->idevents.'/audits', $headers);

        $response->assertSuccessful();
        $heading = collect($response->json('data'))->firstWhere('event', 'updated')['heading'];
        $this->assertStringContainsString('Audit Admin', $heading);
        $this->assertStringNotContainsString(':user_name', $heading);
    }
}
