<?php

namespace Tests\Feature\Groups;

use App\Group;
use App\User;
use Tests\TestCase;

class GroupReportingLinkTest extends TestCase
{
    private $group;

    private function viewGroup()
    {
        return $this->get('/group/view/' . $this->group->idgroups);
    }

    protected function setUp(): void
    {
        parent::setUp();

        config(['restarters.reporting.group_url' => 'https://reports.example.org/dashboard/3?group_id={group}#hide_parameters=group_id']);

        $this->group = Group::factory()->create(['approved' => true]);
    }

    private function reportingUrl($response): ?string
    {
        foreach ($this->getVueProperties($response) as $prop) {
            if (($prop['VueComponent'] ?? null) === 'grouppage') {
                return $prop['reporting-url'] ?? null;
            }
        }

        $this->fail('No GroupPage on the page');
    }

    public function testHostGetsALinkToTheirGroupsReports(): void
    {
        $host = User::factory()->host()->create();
        $this->group->addVolunteer($host);
        $this->group->makeMemberAHost($host);
        $this->actingAs($host);

        $this->assertEquals(
            'https://reports.example.org/dashboard/3?group_id=' . $this->group->idgroups . '#hide_parameters=group_id',
            $this->reportingUrl($this->viewGroup())
        );
    }

    public function testGroupMemberGetsTheLink(): void
    {
        $volunteer = User::factory()->restarter()->create();
        $this->group->addVolunteer($volunteer);
        $this->actingAs($volunteer);

        $this->assertStringContainsString(
            'group_id=' . $this->group->idgroups,
            $this->reportingUrl($this->viewGroup())
        );
    }

    public function testNonMemberDoesNotGetTheLink(): void
    {
        $this->actingAs(User::factory()->restarter()->create());

        $this->assertEmpty($this->reportingUrl($this->viewGroup()));
    }

    public function testNoLinkWhenReportingIsNotConfigured(): void
    {
        config(['restarters.reporting.group_url' => null]);

        $this->actingAs(User::factory()->administrator()->create());

        $this->assertEmpty($this->reportingUrl($this->viewGroup()));
    }
}
