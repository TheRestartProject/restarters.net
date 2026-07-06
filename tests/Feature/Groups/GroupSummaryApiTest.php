<?php

namespace Tests\Feature\Groups;

use App\Group;
use App\GroupTags;
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
}
