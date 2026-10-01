<?php

namespace Tests\Feature\Groups;

use App\Group;
use App\GroupTags;
use App\Network;
use App\Party;
use Carbon\Carbon;
use DB;
use Tests\TestCase;

class GroupSummaryApiTest extends TestCase
{
    public function testArchivedGroupsAreExcludedByDefault(): void
    {
        $active = Group::factory()->create(['name' => 'Active Summary Group']);
        $archived = Group::factory()->create([
            'name' => 'Archived Summary Group',
            'archived_at' => Carbon::now(),
        ]);

        // The endpoint validates an `archived` parameter; it must also honour it.
        $response = $this->get('/api/v2/groups/summary');
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($active->idgroups));
        $this->assertFalse($ids->contains($archived->idgroups));

        $response = $this->get('/api/v2/groups/summary?archived=true');
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($active->idgroups));
        $this->assertTrue($ids->contains($archived->idgroups));
    }

    public function testIncludesGroupTagsForBadgesAndFiltering(): void
    {
        $group = Group::factory()->create();
        $tag = GroupTags::factory()->create();
        $group->addTag($tag);

        $response = $this->get('/api/v2/groups/summary');
        $response->assertSuccessful();

        $summary = collect($response->json('data'))->firstWhere('id', $group->idgroups);
        $this->assertNotNull($summary);
        $this->assertArrayHasKey('group_tags_full', $summary);
        $this->assertEquals($tag->id, $summary['group_tags_full'][0]['id']);
        $this->assertEquals($tag->tag_name, $summary['group_tags_full'][0]['name']);
    }

    public function testQueryCountDoesNotScaleWithGroupCount(): void
    {
        Group::factory()->count(3)->create();

        // Warm up (first request may have extra overhead).
        $this->get('/api/v2/groups/summary?includeNextEvent=true&includeCounts=true');

        DB::enableQueryLog();
        $this->get('/api/v2/groups/summary?includeNextEvent=true&includeCounts=true')->assertSuccessful();
        $queriesForFew = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();

        Group::factory()->count(6)->create();

        DB::enableQueryLog();
        $this->get('/api/v2/groups/summary?includeNextEvent=true&includeCounts=true')->assertSuccessful();
        $queriesForMany = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();

        // Tripling the groups must not grow the queries: relations should be
        // eager-loaded, not fetched per group.
        $this->assertLessThan(
            $queriesForFew * 1.5,
            $queriesForMany,
            "Summary endpoint queries scale with group count: $queriesForFew -> $queriesForMany"
        );
    }

    public function testIdsParamHydratesOnlyThoseGroups(): void
    {
        $network = Network::factory()->create();
        $tag = GroupTags::factory()->create();
        $a = Group::factory()->create(['name' => 'Hydrate A']);
        $b = Group::factory()->create(['name' => 'Hydrate B']);
        $other = Group::factory()->create(['name' => 'Not Asked For']);
        $a->addTag($tag);
        $network->addGroup($a);
        Party::factory()->create([
            'group' => $a->idgroups,
            'event_start_utc' => Carbon::now()->addDays(3)->toIso8601String(),
            'event_end_utc' => Carbon::now()->addDays(3)->addHours(2)->toIso8601String(),
            'approved' => true,
        ]);
        \Cache::forget('future_approved_events');

        $response = $this->get('/api/v2/groups/summary?ids=' . $a->idgroups . ',' . $b->idgroups
            . '&includeNextEvent=true&includeCounts=true&archived=true');
        $response->assertSuccessful();
        $data = collect($response->json('data'));

        // Only the requested groups, with the full row shape the list needs.
        $this->assertEqualsCanonicalizing([$a->idgroups, $b->idgroups], $data->pluck('id')->all());
        $ga = $data->firstWhere('id', $a->idgroups);
        $this->assertEquals($network->id, $ga['networks'][0]['id']);
        $this->assertNotNull($ga['next_event']);
        $this->assertArrayHasKey('hosts', $ga);
        $this->assertEquals($tag->id, $ga['group_tags_full'][0]['id']);
    }

    public function testIdsParamRejectsMoreThanTwoHundred(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->get('/api/v2/groups/summary?ids=' . implode(',', range(1, 201)));
    }

    public function testNamesIndexCarriesFilterFields(): void
    {
        $network = Network::factory()->create();
        $tag = GroupTags::factory()->create();
        $group = Group::factory()->create([
            'name' => 'Index Group',
            'country_code' => 'GB',
        ]);
        $group->addTag($tag);
        $network->addGroup($group);

        $response = $this->get('/api/v2/groups/names?includeArchived=true');
        $response->assertSuccessful();
        $g = collect($response->json('data'))->firstWhere('id', $group->idgroups);
        $this->assertNotNull($g);

        // The map/list index: identity + position + everything the client-side
        // filters need, and nothing heavier.
        $this->assertEqualsCanonicalizing(
            ['id', 'name', 'lat', 'lng', 'archived_at', 'country', 'network_ids', 'tag_ids'],
            array_keys($g)
        );
        $this->assertEquals('United Kingdom', $g['country']);
        $this->assertEquals([$network->id], $g['network_ids']);
        $this->assertEquals([$tag->id], $g['tag_ids']);
    }
}
