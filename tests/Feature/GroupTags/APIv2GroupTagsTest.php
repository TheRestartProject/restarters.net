<?php

namespace Tests\Feature\GroupTags;

use App\GroupTags;
use App\Network;
use App\User;
use Tests\TestCase;

class APIv2GroupTagsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function testListGroupTagsReturnsOnlyGlobalTags(): void
    {
        GroupTags::factory()->create(['tag_name' => 'GlobalA', 'network_id' => null]);
        GroupTags::factory()->create(['tag_name' => 'GlobalB', 'network_id' => null]);

        $network = Network::factory()->create();
        GroupTags::factory()->create(['tag_name' => 'NetworkScoped', 'network_id' => $network->id]);

        $response = $this->get('/api/v2/group-tags');
        $response->assertSuccessful();

        $names = array_column($response->json('data'), 'name');
        $this->assertContains('GlobalA', $names);
        $this->assertContains('GlobalB', $names);
        $this->assertNotContains('NetworkScoped', $names, 'Network-scoped tags must not leak into the global list');
    }

    public function testListGroupTagsOrderedAlphabetically(): void
    {
        GroupTags::factory()->create(['tag_name' => 'Zeppelin', 'network_id' => null]);
        GroupTags::factory()->create(['tag_name' => 'Aardvark', 'network_id' => null]);
        GroupTags::factory()->create(['tag_name' => 'Mongoose', 'network_id' => null]);

        $response = $this->get('/api/v2/group-tags');
        $response->assertSuccessful();
        $names = array_column($response->json('data'), 'name');
        $filtered = array_values(array_filter($names, fn ($n) => in_array($n, ['Zeppelin', 'Aardvark', 'Mongoose'])));
        $this->assertEquals(['Aardvark', 'Mongoose', 'Zeppelin'], $filtered);
    }

    public function testGetSingleGroupTag(): void
    {
        $tag = GroupTags::factory()->create([
            'tag_name' => 'Reusable',
            'description' => 'For reusable item events',
            'network_id' => null,
        ]);

        $response = $this->getJson("/api/v2/group-tags/{$tag->id}");
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertEquals('Reusable', $data['name']);
        $this->assertEquals('For reusable item events', $data['description']);
        $this->assertNull($data['network_id']);
    }

    public function testGetNetworkScopedTagViaGlobalEndpointReturns404(): void
    {
        $network = Network::factory()->create();
        $tag = GroupTags::factory()->create(['network_id' => $network->id]);

        // The global endpoint must refuse to expose network-scoped tags so admins
        // can't accidentally manage them from the global page.
        $response = $this->getJson("/api/v2/group-tags/{$tag->id}");
        $response->assertStatus(404);
    }

    public function testGetMissingGroupTagReturns404(): void
    {
        $response = $this->getJson('/api/v2/group-tags/99999999');
        $response->assertStatus(404);
    }

    public function testCreateGroupTagAsAdmin(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $response = $this->postJson('/api/v2/group-tags?api_token=admin1', [
            'name' => 'Scotland',
            'description' => 'Groups in Scotland',
        ]);
        $response->assertStatus(201);
        $data = $response->json('data');
        $this->assertEquals('Scotland', $data['name']);
        $this->assertNull($data['network_id']);
        $this->assertDatabaseHas('group_tags', [
            'tag_name' => 'Scotland',
            'network_id' => null,
        ]);
    }

    public function testCreateGroupTagRequiresAuth(): void
    {
        $response = $this->postJson('/api/v2/group-tags', ['name' => 'X']);
        $response->assertStatus(401);
    }

    public function testCreateGroupTagForbiddenForNonAdmin(): void
    {
        $user = User::factory()->restarter()->create(['api_token' => 'r1']);
        $this->actingAs($user);

        $response = $this->postJson('/api/v2/group-tags?api_token=r1', ['name' => 'Forbidden']);
        $response->assertStatus(403);
    }

    public function testCreateGroupTagValidationFails(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $this->postJson('/api/v2/group-tags?api_token=admin1', [])->assertStatus(422);
        $this->postJson('/api/v2/group-tags?api_token=admin1', ['name' => ''])->assertStatus(422);
    }

    public function testCreateGroupTagRejectsDuplicateGlobalName(): void
    {
        GroupTags::factory()->create(['tag_name' => 'Existing', 'network_id' => null]);
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $response = $this->postJson('/api/v2/group-tags?api_token=admin1', ['name' => 'Existing']);
        $response->assertStatus(422);
    }

    public function testCreateGroupTagAllowsNameThatOnlyExistsInANetwork(): void
    {
        $network = Network::factory()->create();
        GroupTags::factory()->create(['tag_name' => 'Reused', 'network_id' => $network->id]);

        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        // A global tag with the same name as a network-scoped tag is allowed (they
        // live in different scopes); only collisions within the global scope fail.
        $response = $this->postJson('/api/v2/group-tags?api_token=admin1', ['name' => 'Reused']);
        $response->assertStatus(201);
    }

    public function testUpdateGroupTagAsAdmin(): void
    {
        $tag = GroupTags::factory()->create(['tag_name' => 'OldName', 'network_id' => null]);
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $response = $this->putJson("/api/v2/group-tags/{$tag->id}?api_token=admin1", [
            'name' => 'NewName',
            'description' => 'Renamed',
        ]);
        $response->assertSuccessful();
        $this->assertDatabaseHas('group_tags', [
            'id' => $tag->id,
            'tag_name' => 'NewName',
            'description' => 'Renamed',
        ]);
    }

    public function testUpdateAllowsSameNameAsItself(): void
    {
        $tag = GroupTags::factory()->create(['tag_name' => 'KeepName', 'network_id' => null]);
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $response = $this->putJson("/api/v2/group-tags/{$tag->id}?api_token=admin1", [
            'name' => 'KeepName',
        ]);
        $response->assertSuccessful();
    }

    public function testCannotUpdateNetworkScopedTagViaGlobalEndpoint(): void
    {
        $network = Network::factory()->create();
        $tag = GroupTags::factory()->create(['network_id' => $network->id]);

        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $response = $this->putJson("/api/v2/group-tags/{$tag->id}?api_token=admin1", ['name' => 'Hijacked']);
        $response->assertStatus(404);
    }

    public function testUpdateGroupTagRequiresAuth(): void
    {
        $tag = GroupTags::factory()->create(['network_id' => null]);
        $response = $this->putJson("/api/v2/group-tags/{$tag->id}", ['name' => 'X']);
        $response->assertStatus(401);
    }

    public function testUpdateGroupTagForbiddenForNonAdmin(): void
    {
        $tag = GroupTags::factory()->create(['network_id' => null]);
        $user = User::factory()->restarter()->create(['api_token' => 'r1']);
        $this->actingAs($user);

        $response = $this->putJson("/api/v2/group-tags/{$tag->id}?api_token=r1", ['name' => 'NoTouch']);
        $response->assertStatus(403);
    }

    public function testDeleteGroupTagAsAdmin(): void
    {
        $tag = GroupTags::factory()->create(['network_id' => null]);
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $response = $this->delete("/api/v2/group-tags/{$tag->id}?api_token=admin1");
        $response->assertNoContent();
        $this->assertDatabaseMissing('group_tags', ['id' => $tag->id]);
    }

    public function testCannotDeleteNetworkScopedTagViaGlobalEndpoint(): void
    {
        $network = Network::factory()->create();
        $tag = GroupTags::factory()->create(['network_id' => $network->id]);

        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $response = $this->delete("/api/v2/group-tags/{$tag->id}?api_token=admin1");
        $response->assertStatus(404);
        $this->assertDatabaseHas('group_tags', ['id' => $tag->id]);
    }

    public function testDeleteGroupTagRequiresAuth(): void
    {
        $tag = GroupTags::factory()->create(['network_id' => null]);
        $response = $this->deleteJson("/api/v2/group-tags/{$tag->id}");
        $response->assertStatus(401);
    }

    public function testDeleteGroupTagForbiddenForNonAdmin(): void
    {
        $tag = GroupTags::factory()->create(['network_id' => null]);
        $user = User::factory()->restarter()->create(['api_token' => 'r1']);
        $this->actingAs($user);

        $response = $this->delete("/api/v2/group-tags/{$tag->id}?api_token=r1");
        $response->assertStatus(403);
    }
}
