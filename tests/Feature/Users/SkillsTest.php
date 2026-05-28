<?php

namespace Tests\Feature;

use App\Role;
use App\Skills;
use Tests\TestCase;

class SkillsTest extends TestCase
{
    public function testSkillsAdminPageRendersForAdministrator(): void
    {
        $this->loginAsTestUser(Role::RESTARTER);
        $this->get('/skills')->assertRedirect('/user/forbidden');

        Skills::factory()->create(['skill_name' => 'UT1', 'category' => 1, 'description' => 'Planning']);

        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $response = $this->get('/skills');
        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('<SkillsPage', $html);
        $this->assertMatchesRegularExpression(
            '/:initial-skills="\[[^"]*&quot;skill_name&quot;:&quot;UT1&quot;[^"]*\]"/',
            $html,
            'Expected the skill to appear inside the :initial-skills prop'
        );
        $this->assertStringContainsString(':initial-edit-id="null"', $html);
        $this->assertStringContainsString(':skill-categories=', $html);
    }

    public function testLegacyEditUrlPreOpensEditModalForSkill(): void
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $skill = Skills::factory()->create(['skill_name' => 'Bookmark target', 'category' => 1]);

        $response = $this->get('/skills/edit/' . $skill->id);
        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('<SkillsPage', $html);
        $this->assertStringContainsString(':initial-edit-id="' . $skill->id . '"', $html);
    }
}
