<?php

namespace Tests\Feature;

use App\GroupTags;
use App\Network;
use App\Role;
use Tests\TestCase;

class GroupTagsTest extends TestCase
{
    public function testGroupTagsAdminPageRendersForAdministrator(): void
    {
        $this->loginAsTestUser(Role::RESTARTER);
        $this->get('/tags')->assertRedirect('/user/forbidden');

        $tag = GroupTags::factory()->create(['tag_name' => 'Scotland', 'network_id' => null]);

        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $response = $this->get('/tags');
        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('<GroupTagsPage', $html);
        $this->assertMatchesRegularExpression(
            '/:initial-tags="\[[^"]*&quot;name&quot;:&quot;Scotland&quot;[^"]*\]"/',
            $html,
            'Expected the global tag to appear inside the :initial-tags prop'
        );
        $this->assertStringContainsString(':initial-edit-id="null"', $html);
    }

    public function testAdminPageExcludesNetworkScopedTags(): void
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $network = Network::factory()->create();
        GroupTags::factory()->create(['tag_name' => 'NetworkOnly', 'network_id' => $network->id]);
        GroupTags::factory()->create(['tag_name' => 'GlobalOne', 'network_id' => null]);

        $response = $this->get('/tags');
        $html = $response->getContent();

        $this->assertStringContainsString('GlobalOne', $html);
        // Network-scoped tags live on the per-network page, not here
        $this->assertStringNotContainsString('NetworkOnly', $html);
    }

    public function testLegacyEditUrlPreOpensEditModalForGlobalTag(): void
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $tag = GroupTags::factory()->create(['tag_name' => 'Bookmark target', 'network_id' => null]);

        $response = $this->get('/tags/edit/' . $tag->id);
        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('<GroupTagsPage', $html);
        $this->assertStringContainsString(':initial-edit-id="' . $tag->id . '"', $html);
    }
}
