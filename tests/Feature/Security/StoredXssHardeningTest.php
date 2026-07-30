<?php

namespace Tests\Feature\Security;

use App\Group;
use App\Party;
use App\Role;
use App\User;
use Tests\TestCase;

/**
 * Stored-XSS hardening across the three remaining sink classes:
 *
 *  1. Rich-text description fields (`free_text`) rendered as raw HTML. These are
 *     Quill-authored, so they cannot simply be escaped — they are sanitised on
 *     write instead.
 *  2. Blade's @lang(), which compiles to an unescaped echo, fed user-controlled
 *     replacement values. The audit-log accordion's metadata line is the residual
 *     half of the previously-reported audit-log XSS.
 *  3. Flash messages rendered with {!! !!} whose translation strings interpolate
 *     user-controlled names into HTML.
 *
 * Written before the fixes; these should fail on the unpatched code.
 */
class StoredXssHardeningTest extends TestCase
{
    private string $payload = '<script>alert("XSSPROBE")</script>';
    private string $imgPayload = '<img src=x onerror=alert("XSSPROBE")>';

    // -------------------------------------------------------------------------
    // 1. Rich-text descriptions sanitised on write
    // -------------------------------------------------------------------------

    /** @test */
    public function group_description_is_sanitised_on_save(): void
    {
        $this->loginAsTestUser(Role::HOST);
        $idgroups = $this->createGroup('Sanitise Me', 'https://therestartproject.org', 'London',
            '<p>Legitimate description.</p>' . $this->payload . $this->imgPayload);

        $stored = Group::findOrFail($idgroups)->free_text;

        $this->assertStringNotContainsString('<script', $stored);
        $this->assertStringNotContainsString('onerror', $stored);
        $this->assertStringNotContainsString('alert(', $stored);
        // Legitimate rich text must survive — this is a Quill field, not plain text.
        $this->assertStringContainsString('Legitimate description.', $stored);
        $this->assertStringContainsString('<p>', $stored);
    }

    /** @test */
    public function event_description_is_sanitised_on_save(): void
    {
        $this->loginAsTestUser(Role::HOST);
        $idgroups = $this->createGroup();
        $idevents = $this->createEvent($idgroups, 'tomorrow');

        $event = Party::findOrFail($idevents);

        $this->patch('/api/v2/events/' . $idevents, [
            'start' => '2130-01-01T10:00:00+00:00',
            'end' => '2130-01-01T12:00:00+00:00',
            'title' => $event->venue,
            'location' => $event->location,
            'timezone' => $event->timezone,
            'description' => '<p>Come along.</p>' . $this->payload,
        ]);

        $stored = Party::findOrFail($idevents)->free_text;

        $this->assertStringNotContainsString('<script', $stored);
        $this->assertStringNotContainsString('alert(', $stored);
        $this->assertStringContainsString('Come along.', $stored);
    }

    /** @test */
    public function group_description_is_sanitised_when_updated(): void
    {
        $this->loginAsTestUser(Role::HOST);
        $idgroups = $this->createGroup();

        $this->patch('/api/v2/groups/' . $idgroups, [
            'description' => '<p>Updated.</p>' . $this->imgPayload,
        ]);

        $stored = Group::findOrFail($idgroups)->free_text;

        $this->assertStringNotContainsString('onerror', $stored);
        $this->assertStringContainsString('Updated.', $stored);
    }

    // -------------------------------------------------------------------------
    // 2. @lang() sinks
    // -------------------------------------------------------------------------

    /**
     * The residual half of F005. The reported line (the modified-values cell) was
     * escaped, but the accordion header renders
     * `@lang('group-audits.updated.metadata', $audit->getMetadata())` — and that
     * translation string interpolates :user_name into HTML.
     *
     * @test
     */
    public function audit_log_escapes_the_name_of_the_user_who_made_the_change(): void
    {
        $host = User::factory()->host()->create(['name' => 'Harmless Host']);
        $this->actingAs($host);

        $group = Group::factory()->create(['approved' => true, 'website' => 'https://safe.example.com']);
        \App\UserGroups::create([
            'user' => $host->id,
            'group' => $group->idgroups,
            'status' => 1,
            'role' => Role::HOST,
        ]);

        // Make an audited change so there is an audit row attributed to this user.
        $group->website = 'https://changed.example.com';
        $group->save();

        // The attacker's display name is the payload. getMetadata() resolves the
        // name at render time, so setting it after the edit is equivalent.
        $host->name = $this->payload;
        $host->save();

        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $response = $this->get('/group/edit/' . $group->idgroups);
        $response->assertStatus(200);

        $response->assertDontSee('<script>alert("XSSPROBE")', false);
        $response->assertSee('&lt;script&gt;', false);
    }

    /** @test */
    public function audit_log_escapes_the_request_url(): void
    {
        $host = User::factory()->host()->create();
        $this->actingAs($host);

        $group = Group::factory()->create(['approved' => true]);
        \App\UserGroups::create([
            'user' => $host->id,
            'group' => $group->idgroups,
            'status' => 1,
            'role' => Role::HOST,
        ]);

        // The audit URL resolver records the full request URL, query string included.
        $this->patch('/api/v2/groups/' . $group->idgroups . '?x=' . urlencode($this->imgPayload), [
            'description' => '<p>A harmless edit.</p>',
        ]);

        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $response = $this->get('/group/edit/' . $group->idgroups);
        $response->assertStatus(200);
        $response->assertDontSee('<img src=x', false);
    }

    /** @test */
    public function profile_page_escapes_the_users_name_in_the_no_bio_message(): void
    {
        $attacker = User::factory()->restarter()->create([
            'name' => $this->payload,
            'biography' => null,
        ]);
        $viewer = User::factory()->administrator()->create();

        $this->actingAs($viewer);

        $response = $this->get('/profile/' . $attacker->id);
        $response->assertStatus(200);

        $response->assertDontSee('<script>alert("XSSPROBE")', false);
    }

    // -------------------------------------------------------------------------
    // 3. Flash messages
    // -------------------------------------------------------------------------

    /**
     * `groups.now_following` is 'You are now following <a href=":link">:name</a>!'
     * and is rendered with {!! !!}, so a malicious group name executes in the
     * browser of any user who follows that group.
     *
     * @test
     */
    public function following_a_group_escapes_the_group_name_in_the_flash_message(): void
    {
        $host = User::factory()->host()->create();
        $group = Group::factory()->create([
            'approved' => true,
            'name' => 'Evil ' . $this->imgPayload,
        ]);
        \App\UserGroups::create([
            'user' => $host->id,
            'group' => $group->idgroups,
            'status' => 1,
            'role' => Role::HOST,
        ]);

        $victim = User::factory()->restarter()->create();
        $this->actingAs($victim);

        $this->get('/group/join/' . $group->idgroups);

        $response = $this->followingRedirects()->get('/group/view/' . $group->idgroups);

        // Escaping only rewrites < > and quotes, so assert on the raw tag opening: the
        // literal "onerror=alert" text survives escaping and would false-pass.
        $response->assertDontSee('<img src=x', false);
    }
}
