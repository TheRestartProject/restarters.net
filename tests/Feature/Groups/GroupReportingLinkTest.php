<?php

namespace Tests\Feature\Groups;

use App\Group;
use App\Network;
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

        config(['restarters.reporting.group_urls' => [
            'en' => 'https://reports.example.org/dashboard/3?group_id={group}#hide_parameters=group_id',
            'fr' => 'https://reports.example.org/dashboard/4?group_id={group}#hide_parameters=group_id',
        ]]);

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
        config(['restarters.reporting.group_urls' => ['en' => null, 'fr' => null]]);

        $this->actingAs(User::factory()->administrator()->create());

        $this->assertEmpty($this->reportingUrl($this->viewGroup()));
    }

    private function groupInNetworkWithLanguage(?string $language): Group
    {
        $network = Network::factory()->create(['default_language' => $language]);
        $network->addGroup($this->group);

        return $this->group->fresh();
    }

    public function testFrenchNetworkGetsTheFrenchReports(): void
    {
        $this->assertStringContainsString('/dashboard/4?', $this->groupInNetworkWithLanguage('fr')->reportingUrl());
    }

    public function testEnglishNetworkGetsTheEnglishReports(): void
    {
        $this->assertStringContainsString('/dashboard/3?', $this->groupInNetworkWithLanguage('en')->reportingUrl());
    }

    public function testOtherLanguagesFallBackToEnglish(): void
    {
        $this->assertStringContainsString('/dashboard/3?', $this->groupInNetworkWithLanguage('de')->reportingUrl());
    }

    public function testFallsBackToEnglishWhenThereIsNoFrenchDashboard(): void
    {
        config(['restarters.reporting.group_urls.fr' => null]);

        $this->assertStringContainsString('/dashboard/3?', $this->groupInNetworkWithLanguage('fr')->reportingUrl());
    }

    public function testGroupWithoutANetworkGetsTheEnglishReports(): void
    {
        $this->assertStringContainsString('/dashboard/3?', $this->group->reportingUrl());
    }

    public function testGroupNameIsFilledInEncoded(): void
    {
        config(['restarters.reporting.group_urls.en' => 'https://reports.example.org/d?group_id={group}&group_name={group_name}']);
        $this->group->name = 'Ulverston Repair Café & Co';

        $this->assertEquals(
            'https://reports.example.org/d?group_id=' . $this->group->idgroups . '&group_name=Ulverston%20Repair%20Caf%C3%A9%20%26%20Co',
            $this->group->reportingUrl()
        );
    }
}
