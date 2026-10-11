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
 *  2. Translation strings rendered unescaped (the SPA's v-html), fed user-controlled
 *     replacement values. The audit-log endpoints are the server-side half; the
 *     client escapes the group name it puts in the "unfollowed" banner (covered in
 *     client/tests/pages/group/view.spec.js).
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
    // 2. Translation strings rendered unescaped
    // -------------------------------------------------------------------------

    /**
     * The audit endpoints return the log lines as HTML for the SPA's log tab to render with v-html.
     * The translation strings wrap their :placeholders in markup and the translator does not escape
     * its replacements, so the values have to be escaped before they are substituted: :user_name is a
     * display name, and the old/new values are whatever was typed into the group or event.
     *
     * @test
     */
    public function group_audit_log_escapes_the_user_name_and_the_changed_values(): void
    {
        $host = User::factory()->host()->create(['name' => 'Harmless Host']);
        $this->actingAs($host);

        $group = Group::factory()->create(['approved' => true, 'name' => 'Safe name']);
        \App\UserGroups::create([
            'user' => $host->id,
            'group' => $group->idgroups,
            'status' => 1,
            'role' => Role::HOST,
        ]);

        // An audited change whose new value is the payload.
        $group->name = $this->imgPayload;
        $group->save();

        // getMetadata() resolves the user's name at render time, so setting it after the edit is
        // equivalent to the attacker having it all along.
        $host->name = $this->payload;
        $host->save();

        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $response = $this->getJson('/api/v2/groups/' . $group->idgroups . '/audits');
        $response->assertStatus(200);

        $body = $response->getContent();
        $this->assertStringNotContainsString('<script>alert', $body);
        $this->assertStringNotContainsString('<img src=x', $body);
        $this->assertStringContainsString('&lt;script&gt;', $body);
        $this->assertStringContainsString('&lt;img', $body);
        // ...while the markup of the translation string itself survives.
        $this->assertStringContainsString('<strong>', $body);
    }

    /** @test */
    public function group_audit_log_escapes_the_request_url(): void
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

        // The audit URL resolver records the request URL; the path is attacker-influenced too.
        $this->patch('/api/v2/groups/' . $group->idgroups . '?x=' . urlencode($this->imgPayload), [
            'description' => '<p>A harmless edit.</p>',
        ]);

        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $response = $this->getJson('/api/v2/groups/' . $group->idgroups . '/audits');
        $response->assertStatus(200);
        $this->assertStringNotContainsString('<img src=x', $response->getContent());
    }

    /** @test */
    public function event_audit_log_escapes_the_user_name_and_the_changed_values(): void
    {
        $host = User::factory()->host()->create(['name' => 'Harmless Host']);
        $this->actingAs($host);

        $group = Group::factory()->create(['approved' => true]);
        \App\UserGroups::create([
            'user' => $host->id,
            'group' => $group->idgroups,
            'status' => 1,
            'role' => Role::HOST,
        ]);
        $event = Party::factory()->create(['group' => $group->idgroups, 'venue' => 'Safe venue']);

        $event->venue = $this->imgPayload;
        $event->save();

        $host->name = $this->payload;
        $host->save();

        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $response = $this->getJson('/api/v2/events/' . $event->idevents . '/audits');
        $response->assertStatus(200);

        $body = $response->getContent();
        $this->assertStringNotContainsString('<script>alert', $body);
        $this->assertStringNotContainsString('<img src=x', $body);
    }

    /**
     * The sanitiser must not quietly degrade the rich text that already exists. This
     * asserts against the constructs found in a survey of the live data: links opening
     * in a new tab (~5,100 of them), inline styles (~7,100), Quill's alignment classes
     * (~1,300), plus div, hr, sub/sup and the occasional table.
     *
     * @test
     */
    public function sanitiser_preserves_the_rich_text_the_live_content_actually_uses(): void
    {
        $this->loginAsTestUser(Role::HOST);

        $rich = '<p class="ql-align-center" style="text-align: center;">Centred</p>'
            . '<p><a href="https://therestartproject.org" target="_blank" rel="noopener">A link</a></p>'
            . '<p><span style="font-size: 14px; color: rgb(0, 0, 0);">Styled</span></p>'
            . '<div>A div</div><hr><p>H<sub>2</sub>O and x<sup>2</sup></p>'
            . '<table><tbody><tr><td>Mon</td><td>10-4</td></tr></tbody></table>'
            . '<ul><li>One</li></ul><blockquote>Quoted</blockquote><h4>A heading</h4>';

        $idgroups = $this->createGroup('Rich Text Group', 'https://therestartproject.org', 'London', $rich);
        $stored = Group::findOrFail($idgroups)->free_text;

        foreach ([
            'target="_blank"' => 'links opening in a new tab',
            'class="ql-align-center"' => "Quill's alignment classes",
            'font-size' => 'inline font styling',
            '<div>' => 'divs',
            '<hr' => 'horizontal rules',
            '<sub>' => 'subscript',
            '<sup>' => 'superscript',
            '<table>' => 'tables',
            '<blockquote>' => 'blockquotes',
            '<h4>' => 'headings',
        ] as $needle => $what) {
            $this->assertStringContainsString($needle, $stored, "Sanitiser stripped $what");
        }
    }

    /**
     * The corresponding negative: the things that make a payload dangerous are removed
     * even though the allowlist is wide.
     *
     * @test
     */
    public function sanitiser_removes_script_handlers_and_javascript_urls(): void
    {
        $this->loginAsTestUser(Role::HOST);

        $nasty = '<p>Text</p><script>alert(1)</script>'
            . '<img src=x onerror="alert(2)">'
            . '<a href="javascript:alert(3)">Click</a>'
            . '<iframe src="https://evil.example/"></iframe>'
            . '<div style="background: url(javascript:alert(4))">Styled</div>'
            . '<form action="https://evil.example/"><input name="p"></form>';

        $idgroups = $this->createGroup('Nasty Group', 'https://therestartproject.org', 'London', $nasty);
        $stored = Group::findOrFail($idgroups)->free_text;

        foreach (['<script', 'onerror', 'javascript:', '<iframe', '<form', '<input'] as $needle) {
            $this->assertStringNotContainsString($needle, $stored, "Sanitiser kept $needle");
        }

        // ...while the surrounding legitimate content survives.
        $this->assertStringContainsString('Text', $stored);
    }
}
