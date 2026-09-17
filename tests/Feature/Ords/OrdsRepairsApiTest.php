<?php

namespace Tests\Feature\Ords;

use App\Barrier;
use App\Category;
use App\Device;
use App\Group;
use App\Party;
use App\Services\Ords\OrdsRecordMapper;
use App\User;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * @see app/Http/Controllers/API/RepairController.php
 * @see config/ords.php
 */
class OrdsRepairsApiTest extends TestCase
{
    private const EVENT_START_UTC = '2024-06-15 18:00:00';

    private ?string $apiToken = null;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            // Deployments ship with the placeholder, which the controller
            // refuses to serve under; tests need a usable namespace.
            'ords.id_prefix' => 'testinstance_',
            'ords.data_provider' => 'Test Repair Org',
        ]);
    }

    // ---------------------------------------------------------------- auth

    /**
     * Note: Handler::render returns 500 for any JSON exception that carries no
     * status code, and AuthenticationException is one, so an unauthenticated
     * call to any endpoint on this API surfaces as a 500 rather than a 401.
     * These tests assert that the request is refused rather than asserting the
     * status, so they cover the auth gate without baking in that pre-existing
     * behaviour.
     */
    public function test_requires_authentication(): void
    {
        $this->withExceptionHandling();

        $response = $this->getJson('/api/v2/repairs');

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('Unauthenticated.', $response->json('message'));
    }

    public function test_rejects_an_unknown_token(): void
    {
        $this->withExceptionHandling();

        $response = $this->getJson('/api/v2/repairs?api_token=not_a_real_token');

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('Unauthenticated.', $response->json('message'));
    }

    public function test_refuses_a_caller_who_is_not_an_administrator(): void
    {
        // This is a bulk export of volunteer-written text across every approved
        // group, so it is gated the same way the other global exports are.
        $this->withExceptionHandling();

        $this->seedRepair();

        foreach (['restarter', 'host', 'networkCoordinator'] as $role) {
            $token = User::factory()->{$role}()->create()->ensureAPIToken();

            $this->getJson('/api/v2/repairs?'.http_build_query(['api_token' => $token]))
                ->assertStatus(403);
        }
    }

    // ------------------------------------------------------ id namespace

    public function test_refuses_to_serve_under_an_unassigned_id_namespace(): void
    {
        // ORDS ids are a stable key the Open Repair Alliance updates in place
        // across releases, so publishing under a namespace we were not assigned
        // would overwrite another provider's rows.
        config(['ords.id_prefix' => OrdsRecordMapper::UNASSIGNED_ID_PREFIX]);

        $this->seedRepair();

        $response = $this->getJson($this->url());

        $response->assertStatus(503);
        $this->assertStringContainsString('id namespace', $response->json('message'));
    }

    public function test_refuses_to_serve_under_a_blank_id_namespace(): void
    {
        // An env var set to "" yields an empty string rather than falling back
        // to the config default, so a deployment shipping ORDS_ID_PREFIX="" must
        // not be mistaken for a configured export.
        config(['ords.id_prefix' => '']);

        $this->seedRepair();

        $this->getJson($this->url())->assertStatus(503);
    }

    public function test_refuses_a_prefix_that_env_casting_turned_into_a_boolean(): void
    {
        // ORDS_ID_PREFIX=true in a .env file reaches config() as boolean true,
        // and "(string) true" is "1", which used to pass the guard and publish
        // every record under a one-character namespace. "false", "null" and
        // "empty" already failed closed; only "true" did not.
        config(['ords.id_prefix' => true]);

        $this->seedRepair();

        $this->getJson($this->url())->assertStatus(503);
    }

    public function test_refuses_a_data_provider_that_env_casting_turned_into_a_boolean(): void
    {
        config(['ords.data_provider' => true]);

        $this->seedRepair();

        $this->getJson($this->url())->assertStatus(503);
    }

    public function test_refuses_to_serve_without_a_data_provider(): void
    {
        // data_provider is the attribution carried on every published row.
        config(['ords.data_provider' => '']);

        $this->seedRepair();

        $response = $this->getJson($this->url());

        $response->assertStatus(503);
        $this->assertStringContainsString('data provider', $response->json('message'));
    }

    public function test_emits_ids_under_the_assigned_namespace(): void
    {
        config(['ords.id_prefix' => 'restarters_']);

        $device = $this->seedRepair();

        $this->assertEquals(
            'restarters_'.$device->iddevices,
            $this->fetchRecords()[0]['id']
        );
    }

    public function test_a_padded_id_prefix_is_trimmed_before_emission(): void
    {
        // The config guard validates the trimmed prefix, so the emitted id has
        // to be trimmed too or " restarters_ " passes the guard and publishes
        // ids carrying a leading space.
        config(['ords.id_prefix' => ' restarters_ ']);

        $device = $this->seedRepair();

        $this->assertSame('restarters_'.$device->iddevices, $this->fetchRecords()[0]['id']);
    }

    // ------------------------------------------------------------ columns

    public function test_emits_the_fourteen_ords_columns_in_spec_order(): void
    {
        $this->seedRepair();

        $this->assertEquals([
            'id',
            'data_provider',
            'country',
            'partner_product_category',
            'product_category',
            'product_category_id',
            'brand',
            'year_of_manufacture',
            'product_age',
            'repair_status',
            'repair_barrier_if_end_of_life',
            'group_identifier',
            'event_date',
            'problem',
        ], array_keys($this->fetchRecords()[0]));
    }

    public function test_maps_every_column_from_the_fixture(): void
    {
        $device = $this->seedRepair([
            'brand' => 'Acme',
            'item_type' => 'Tower PC',
            'age' => 5,
            'problem' => 'Would not power on. Replaced the PSU.',
            'repair_status' => Device::REPAIR_STATUS_FIXED,
        ]);

        $record = $this->fetchRecords()[0];

        $this->assertEquals('testinstance_'.$device->iddevices, $record['id']);
        $this->assertEquals('Test Repair Org', $record['data_provider']);
        // groups.country_code is alpha-2; ORDS requires alpha-3.
        $this->assertEquals('GBR', $record['country']);
        // The Restart Project's own published rows use "<category> ~ <item_type>".
        $this->assertEquals('Desktop computer ~ Tower PC', $record['partner_product_category']);
        $this->assertEquals('Desktop computer', $record['product_category']);
        // Our idcategories is 11; the standard publishes Desktop computer as 4.
        $this->assertEquals(4, $record['product_category_id']);
        $this->assertEquals('Acme', $record['brand']);
        // 2024 event minus a 5 year old item.
        $this->assertEquals('2019', $record['year_of_manufacture']);
        $this->assertEquals(5, $record['product_age']);
        $this->assertEquals('Fixed', $record['repair_status']);
        $this->assertNull($record['repair_barrier_if_end_of_life']);
        $this->assertEquals('ORDS Group', $record['group_identifier']);
        $this->assertEquals('2024-06-15', $record['event_date']);
        $this->assertEquals('Would not power on. Replaced the PSU.', $record['problem']);
    }

    public function test_event_date_uses_the_events_local_timezone(): void
    {
        // 2024-06-16 06:00 UTC is still 2024-06-15 in Los Angeles.
        $this->seedRepair([], ['start_utc' => '2024-06-16 06:00:00', 'timezone' => 'America/Los_Angeles']);

        $this->assertEquals('2024-06-15', $this->fetchRecords()[0]['event_date']);
    }

    public function test_repair_status_falls_back_to_unknown(): void
    {
        // `devices.repair_status` is NOT NULL DEFAULT 0, and ORDS carries
        // Unknown as a real enum value rather than a blank.
        $this->seedRepair(['repair_status' => 0]);

        $this->assertEquals('Unknown', $this->fetchRecords()[0]['repair_status']);
    }

    public function test_year_of_manufacture_and_age_are_omitted_when_age_is_not_recorded(): void
    {
        // `devices.age` is DECIMAL NOT NULL DEFAULT 0, so 0 is how "we did not
        // record an age" is stored rather than a real age of zero.
        $this->seedRepair(['age' => 0]);

        $record = $this->fetchRecords()[0];
        $this->assertNull($record['year_of_manufacture']);
        $this->assertNull($record['product_age']);
    }

    public function test_fractional_age_is_preserved(): void
    {
        $this->seedRepair(['age' => 2.5]);

        $record = $this->fetchRecords()[0];
        $this->assertEquals(2.5, $record['product_age']);
        // 2024 minus 2.5 years, rounded to a whole year.
        $this->assertEquals('2022', $record['year_of_manufacture']);
    }

    // ----------------------------------------------------------- barriers

    public function test_barrier_is_emitted_only_for_end_of_life_and_uses_ords_wording(): void
    {
        // We seed "No way to open the product"; the standard publishes it
        // without the "the".
        $barrier = Barrier::where('barrier', 'No way to open the product')->firstOrFail();

        $device = $this->seedRepair(['repair_status' => Device::REPAIR_STATUS_ENDOFLIFE]);
        $device->barriers()->attach($barrier->id);

        $this->assertEquals(
            'No way to open product',
            $this->fetchRecords()[0]['repair_barrier_if_end_of_life']
        );
    }

    public function test_barrier_choice_is_stable_across_exports(): void
    {
        // ORDS has one barrier column but a device can carry several, so the
        // mapper takes the first. The record id is a stable key the consumer
        // upserts on, so an unordered relation would republish a different
        // barrier at random.
        $first = Barrier::where('barrier', 'Lack of equipment')->firstOrFail();
        $second = Barrier::where('barrier', 'Spare parts too expensive')->firstOrFail();
        [$lower, $higher] = $first->id < $second->id ? [$first, $second] : [$second, $first];

        $device = $this->seedRepair(['repair_status' => Device::REPAIR_STATUS_ENDOFLIFE]);
        // Attached highest-first so insertion order cannot be what makes this pass.
        $device->barriers()->attach($higher->id);
        $device->barriers()->attach($lower->id);

        // Local literals, not config('ords.barriers')[...]: deriving the
        // expectation from the same map the mapper reads would leave only the
        // barrier-choice half of this assertion load-bearing.
        $vocabulary = [
            'Lack of equipment' => 'Lack of equipment',
            'Spare parts too expensive' => 'Spare parts too expensive',
        ];

        $this->assertEquals(
            $vocabulary[$lower->barrier],
            $this->fetchRecords()[0]['repair_barrier_if_end_of_life']
        );
    }

    public function test_an_unmapped_barrier_is_omitted(): void
    {
        // A barrier outside the ORDS vocabulary is a mapping gap; emitting our
        // own wording would put an invalid value in a constrained column.
        $barrier = Barrier::create(['barrier' => 'Not an ORDS barrier']);

        try {
            $device = $this->seedRepair(['repair_status' => Device::REPAIR_STATUS_ENDOFLIFE]);
            $device->barriers()->attach($barrier->id);

            $this->assertNull($this->fetchRecords()[0]['repair_barrier_if_end_of_life']);
        } finally {
            // TestCase::setUp does not truncate `barriers`, so without this the
            // row survives into test_every_seeded_barrier_has_a_vocabulary_mapping
            // and fails it. In finally, or a failure here becomes two failures.
            // Barrier's inverse relation is itself named barriers(); see App\Barrier.
            $barrier->barriers()->detach();
            $barrier->delete();
        }
    }

    public function test_barrier_is_suppressed_when_the_item_was_repaired(): void
    {
        $barrier = Barrier::where('barrier', 'Lack of equipment')->firstOrFail();

        $device = $this->seedRepair(['repair_status' => Device::REPAIR_STATUS_FIXED]);
        $device->barriers()->attach($barrier->id);

        $this->assertNull($this->fetchRecords()[0]['repair_barrier_if_end_of_life']);
    }

    public function test_every_seeded_barrier_has_a_vocabulary_mapping(): void
    {
        // An unmapped barrier exports as null rather than as our own wording,
        // so a barrier added to the seed without a mapping degrades quietly.
        $map = config('ords.barriers');

        foreach (Barrier::all() as $barrier) {
            $this->assertArrayHasKey(
                $barrier->barrier,
                $map,
                "seeded barrier \"{$barrier->barrier}\" has no ORDS mapping"
            );
        }
    }

    // --------------------------------------------------------- categories

    public function test_unpowered_categories_use_the_ords_unpowered_vocabulary(): void
    {
        $this->seedCategory(501, 'Clothing/textile', false);

        $this->seedRepair(['category' => 501, 'category_creation' => 501, 'item_type' => null]);

        $record = $this->fetchRecords()[0];
        $this->assertEquals('Unpowered - Textile', $record['product_category']);
        // The standard's unpowered dataset carries no product_category_id.
        $this->assertNull($record['product_category_id']);
        $this->assertEquals('Clothing/textile', $record['partner_product_category']);
    }

    public function test_an_unmapped_powered_category_falls_back_to_our_own_name(): void
    {
        // A category outside the ORDS vocabulary is a mapping gap, not a data
        // error. The record stays usable and the gap is visible in the export.
        $this->seedCategory(504, '3D printer', true);

        $this->seedRepair(['category' => 504, 'category_creation' => 504, 'item_type' => null]);

        $record = $this->fetchRecords()[0];
        $this->assertEquals('3D printer', $record['product_category']);
        $this->assertNull($record['product_category_id']);
        $this->assertEquals('3D printer', $record['partner_product_category']);
    }

    /**
     * ORDS requires alpha-3. `groups.country_code` is alpha-2 and nullable, and
     * nothing constrains it to a real code. Split per case: asserted together,
     * the unmappable-code half passes on the null fixture's leftover row.
     */
    public function test_an_unmapped_unpowered_category_uses_the_generic_fallback(): void
    {
        // The reason categories_unpowered_fallback exists: an unpowered category
        // outside the standard's vocabulary still has to land somewhere valid.
        $this->seedCategory(506, 'Unlisted unpowered thing', false);

        $this->seedRepair(['category' => 506, 'category_creation' => 506, 'item_type' => null]);

        $record = $this->fetchRecords()[0];
        $this->assertEquals(config('ords.categories_unpowered_fallback'), $record['product_category']);
        $this->assertNull($record['product_category_id']);
    }

    public function test_year_of_manufacture_is_omitted_when_the_derived_year_is_implausible(): void
    {
        // ORDS constrains this to ^[0-9]{4}$, so a derived year outside 1000-9999
        // is dropped rather than emitted and rejected downstream.
        //
        // Reached via an old event date, not a huge age: `devices.age` is
        // DECIMAL(5,2) so it tops out at 999.99, and a present-day event minus
        // 999.99 years still lands in the 1000s.
        $this->seedRepair(['age' => 999], ['start_utc' => '1990-06-15 18:00:00']);

        $record = $this->fetchRecords()[0];
        $this->assertNull($record['year_of_manufacture']);
        $this->assertEquals(999, $record['product_age']);
    }

    public function test_an_unrecognised_event_timezone_falls_back_to_utc(): void
    {
        // events.timezone is not constrained to a real zone. Rather than drop a
        // required column, the mapper keeps the UTC date.
        $this->seedRepair([], ['start_utc' => '2024-06-15 18:00:00', 'timezone' => 'Not/AZone']);

        $this->assertEquals('2024-06-15', $this->fetchRecords()[0]['event_date']);
    }

    public function test_country_is_null_when_the_group_has_no_country_code(): void
    {
        $this->seedRepair([], ['country_code' => null]);

        $this->assertNull($this->fetchRecords()[0]['country']);
    }

    public function test_country_is_null_when_the_code_is_not_a_real_country(): void
    {
        $this->seedRepair([], ['group' => 'Unknown Country Group', 'country_code' => 'ZZ']);

        $records = $this->fetchRecords();
        $this->assertCount(1, $records);
        $this->assertNull($records[0]['country']);
    }

    // ---------------------------------------------------------- visibility

    public function test_excludes_unapproved_events_unapproved_groups_and_deleted_events(): void
    {
        // Events are soft-deletable, groups are not: a removed group takes its
        // rows out of the inner join, so only events need the explicit filter.
        $visible = $this->seedRepair();

        $unapprovedEventDevice = $this->seedRepair([], ['group' => 'Second Group', 'approve_event' => false]);
        $unapprovedGroupDevice = $this->seedRepair([], ['group' => 'Third Group', 'approve_group' => false]);

        $deletedEventDevice = $this->seedRepair([], ['group' => 'Fourth Group']);
        Party::findOrFail($deletedEventDevice->event)->delete();

        $ids = array_column($this->fetchRecords(), 'id');

        $this->assertContains('testinstance_'.$visible->iddevices, $ids);
        $this->assertNotContains('testinstance_'.$unapprovedEventDevice->iddevices, $ids);
        $this->assertNotContains('testinstance_'.$unapprovedGroupDevice->iddevices, $ids);
        $this->assertNotContains('testinstance_'.$deletedEventDevice->iddevices, $ids);
    }

    // ------------------------------------------------------------ filters

    public function test_powered_filter_selects_each_dataset(): void
    {
        $this->seedCategory(502, 'Bicycle', false);

        $powered = $this->seedRepair();
        $unpowered = $this->seedRepair(['category' => 502, 'category_creation' => 502], ['reuse' => true]);

        $poweredIds = array_column($this->fetchRecords(['powered' => 1]), 'id');
        $this->assertContains('testinstance_'.$powered->iddevices, $poweredIds);
        $this->assertNotContains('testinstance_'.$unpowered->iddevices, $poweredIds);

        $unpoweredIds = array_column($this->fetchRecords(['powered' => 0]), 'id');
        $this->assertContains('testinstance_'.$unpowered->iddevices, $unpoweredIds);
        $this->assertNotContains('testinstance_'.$powered->iddevices, $unpoweredIds);

        // Unfiltered returns both: the standard publishes them as separate
        // datasets, but the endpoint does not silently drop half the data.
        $this->assertCount(2, $this->fetchRecords());
    }

    public function test_powered_filter_accepts_true_and_false_spellings(): void
    {
        // Laravel's `boolean` rule takes only true/false/0/1, so `?powered=true`
        // has to be normalised before validation or it is rejected outright.
        $this->seedCategory(502, 'Bicycle', false);

        $powered = $this->seedRepair();
        $unpowered = $this->seedRepair(['category' => 502, 'category_creation' => 502], ['reuse' => true]);

        $trueIds = array_column($this->fetchRecords(['powered' => 'true']), 'id');
        $this->assertContains('testinstance_'.$powered->iddevices, $trueIds);
        $this->assertNotContains('testinstance_'.$unpowered->iddevices, $trueIds);

        $falseIds = array_column($this->fetchRecords(['powered' => 'false']), 'id');
        $this->assertContains('testinstance_'.$unpowered->iddevices, $falseIds);
        $this->assertNotContains('testinstance_'.$powered->iddevices, $falseIds);
    }

    public function test_an_empty_powered_param_returns_both_datasets(): void
    {
        // An empty param is how plenty of clients spell an unset one.
        $this->seedCategory(502, 'Bicycle', false);

        $powered = $this->seedRepair();
        $unpowered = $this->seedRepair(['category' => 502, 'category_creation' => 502], ['reuse' => true]);

        $ids = array_column($this->fetchRecords(['powered' => '']), 'id');

        $this->assertContains('testinstance_'.$powered->iddevices, $ids);
        $this->assertContains('testinstance_'.$unpowered->iddevices, $ids);
    }

    /**
     * Regression: "date-only" was decided from the parsed value's local
     * H:i:s, which reads "00:00:00" for a full timestamp that lands on midnight
     * in its own offset. event_end=2024-06-15T00:00:00-05:00 means 05:00 UTC,
     * and widening it to end-of-day admitted a further 24 hours of events.
     */
    public function test_a_midnight_event_end_carrying_an_offset_is_taken_as_given(): void
    {
        // Seeded event starts 2024-06-15 18:00:00 UTC.
        $this->seedRepair();

        // 2024-06-15T00:00:00-05:00 is 2024-06-15 05:00:00 UTC, before the event.
        $this->assertEmpty($this->fetchRecords(['event_end' => '2024-06-15T00:00:00-05:00']));

        // 2024-06-16T00:00:00-05:00 is 2024-06-16 05:00:00 UTC, after it.
        $this->assertCount(1, $this->fetchRecords(['event_end' => '2024-06-16T00:00:00-05:00']));

        // An explicit UTC midnight is likewise a boundary, not a whole day.
        $this->assertEmpty($this->fetchRecords(['event_end' => '2024-06-15T00:00:00+00:00']));
        $this->assertEmpty($this->fetchRecords(['event_end' => '2024-06-15 00:00:00']));
    }

    public function test_a_date_only_event_end_includes_that_whole_day(): void
    {
        // The fixture event runs at 18:00 on 2024-06-15. A caller asking for a
        // window ending on that date means to include it.
        $this->seedRepair();

        $this->assertCount(1, $this->fetchRecords(['event_end' => '2024-06-15']));
        $this->assertEmpty($this->fetchRecords(['event_end' => '2024-06-14']));
    }

    public function test_updated_since_filter(): void
    {
        // All three rows are backdated: "unchanged since" now means the device
        // and both of its parents have stood still, not the device alone.
        $device = $this->seedRepair();
        $this->backdate($device, '2000-01-01 00:00:00');

        $this->assertEmpty($this->fetchRecords(['updated_since' => '2010-01-01T00:00:00+00:00']));
        $this->assertCount(1, $this->fetchRecords(['updated_since' => '1999-01-01T00:00:00+00:00']));
    }

    /**
     * The case that made incremental consumers miss data: an event sits
     * unapproved for months, the devices on it are exported by nobody, and
     * approving it never touches devices.updated_at. Keyed on the device row
     * alone those devices stay invisible for good.
     */
    public function test_updated_since_sees_a_device_whose_event_changed_later(): void
    {
        $device = $this->seedRepair();
        $this->backdate($device, '2000-01-01 00:00:00');

        $this->touchRow(Party::class, $device->event, 'idevents', '2020-06-01 12:00:00');

        $this->assertCount(1, $this->fetchRecords(['updated_since' => '2010-01-01T00:00:00+00:00']));
    }

    public function test_updated_since_sees_a_device_whose_group_changed_later(): void
    {
        $device = $this->seedRepair();
        $this->backdate($device, '2000-01-01 00:00:00');

        $groupId = Party::findOrFail($device->event)->group;
        $this->touchRow(Group::class, $groupId, 'idgroups', '2020-06-01 12:00:00');

        $this->assertCount(1, $this->fetchRecords(['updated_since' => '2010-01-01T00:00:00+00:00']));
    }

    /**
     * The watermark has to run on the same clock as the filter, or a consumer
     * resuming from it would skip the parent-row changes the filter just
     * started returning.
     */
    public function test_the_sync_watermark_covers_the_parent_rows_too(): void
    {
        $device = $this->seedRepair();
        $this->backdate($device, '2000-01-01 00:00:00');

        $this->touchRow(Party::class, $device->event, 'idevents', '2020-06-01 12:00:00');

        $response = $this->getJson($this->url());
        $response->assertSuccessful();

        $this->assertEquals(
            '2020-06-01T12:00:00+00:00',
            Carbon::parse($response->json('sync.max_updated_at'))->utc()->toIso8601String()
        );
    }

    public function test_event_window_filters(): void
    {
        $this->seedRepair();

        $this->assertCount(1, $this->fetchRecords([
            'event_start' => '2024-01-01T00:00:00+00:00',
            'event_end' => '2024-12-31T00:00:00+00:00',
        ]));

        $this->assertEmpty($this->fetchRecords(['event_start' => '2025-01-01T00:00:00+00:00']));
        $this->assertEmpty($this->fetchRecords(['event_end' => '2023-01-01T00:00:00+00:00']));
    }

    // --------------------------------------------------------- pagination

    public function test_per_page_ceiling_is_one_thousand(): void
    {
        $this->withExceptionHandling();
        $this->seedRepair();

        $this->getJson($this->url(['per_page' => 1000]))
            ->assertSuccessful()
            ->assertJsonPath('meta.per_page', 1000);

        $this->getJson($this->url(['per_page' => 1001]))->assertStatus(422);
    }

    public function test_pages_cover_every_record_exactly_once(): void
    {
        // A bulk consumer walks this endpoint page by page, so the pages must
        // partition the result set: no row skipped, none served twice.
        $devices = [
            $this->seedRepair(),
            $this->seedRepair([], ['reuse' => true]),
            $this->seedRepair([], ['reuse' => true]),
        ];

        $expected = array_map(fn (Device $d) => 'testinstance_'.$d->iddevices, $devices);

        $first = $this->getJson($this->url(['per_page' => 2]));
        $first->assertSuccessful()
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.last_page', 2);

        $second = $this->getJson($this->url(['per_page' => 2, 'page' => 2]));
        $second->assertSuccessful()->assertJsonPath('meta.page', 2);

        $firstIds = array_column($first->json('data'), 'id');
        $secondIds = array_column($second->json('data'), 'id');
        $all = array_merge($firstIds, $secondIds);

        $this->assertCount(2, $firstIds);
        $this->assertCount(1, $secondIds);
        $this->assertSame($all, array_unique($all));
        $this->assertEqualsCanonicalizing($expected, $all);
    }

    public function test_rejects_an_unknown_format(): void
    {
        $this->withExceptionHandling();

        $this->getJson($this->url(['format' => 'xml']))->assertStatus(422);
    }

    public function test_reports_the_standard_and_columns_in_meta(): void
    {
        $this->seedRepair();

        $this->getJson($this->url())
            ->assertSuccessful()
            // Pinned as a literal: this is the standard version the record
            // shape implements, so a change to the constant should fail here.
            ->assertJsonPath('meta.standard', 'Open Repair Data Standard v0.3')
            // Spelled out rather than compared to OrdsRecordMapper::COLUMNS,
            // which the controller emits verbatim: that would assert a constant
            // equals itself and survive any edit to it.
            ->assertJsonPath('meta.columns', [
                'id', 'data_provider', 'country', 'partner_product_category',
                'product_category', 'product_category_id', 'brand',
                'year_of_manufacture', 'product_age', 'repair_status',
                'repair_barrier_if_end_of_life', 'group_identifier',
                'event_date', 'problem',
            ])
            ->assertJsonStructure(['sync' => ['generated_at', 'max_updated_at']]);
    }

    public function test_response_matches_the_published_openapi_schema(): void
    {
        // TestCase::get validates every /api/v2 response against the generated
        // schema, so this covers the annotation as well as the payload.
        $this->seedRepair();

        $this->get($this->url())->assertSuccessful();
    }

    // ---------------------------------------------------------------- CSV

    public function test_csv_output_matches_the_ords_column_order(): void
    {
        $device = $this->seedRepair([
            'brand' => 'Acme',
            'item_type' => 'Tower PC',
            'age' => 5,
            'repair_status' => Device::REPAIR_STATUS_FIXED,
            'problem' => 'Would not power on.',
        ]);

        $rows = $this->fetchCsvRows();

        $this->assertEquals(OrdsRecordMapper::COLUMNS, $rows[0]);
        $this->assertEquals([
            'testinstance_'.$device->iddevices,
            'Test Repair Org',
            'GBR',
            'Desktop computer ~ Tower PC',
            'Desktop computer',
            '4',
            'Acme',
            '2019',
            '5',
            'Fixed',
            '',
            'ORDS Group',
            '2024-06-15',
            'Would not power on.',
        ], $rows[1]);
    }

    public function test_csv_writes_empty_strings_for_missing_values(): void
    {
        // ORDS declares "" as the missing value for every optional column.
        $this->seedRepair(['brand' => null, 'age' => 0, 'problem' => '']);

        $columns = array_combine(OrdsRecordMapper::COLUMNS, $this->fetchCsvRows()[1]);

        $this->assertSame('', $columns['brand']);
        $this->assertSame('', $columns['year_of_manufacture']);
        $this->assertSame('', $columns['product_age']);
        $this->assertSame('', $columns['problem']);
        $this->assertSame('', $columns['repair_barrier_if_end_of_life']);
    }

    public function test_csv_neutralises_spreadsheet_formulas(): void
    {
        // `problem` is volunteer free text, and Excel and Sheets execute a cell
        // opening with = + - @ as a formula the moment the file is opened.
        $this->seedRepair(['problem' => '=HYPERLINK("http://example.com","click")']);

        $columns = array_combine(OrdsRecordMapper::COLUMNS, $this->fetchCsvRows()[1]);

        $this->assertSame(
            '\'=HYPERLINK("http://example.com","click")',
            $columns['problem']
        );
    }

    public function test_csv_carries_the_pagination_metadata_in_headers(): void
    {
        // CSV has no envelope for `meta`/`sync`, so without these a bulk
        // consumer cannot tell that a second page exists.
        $this->seedRepair();
        $this->seedRepair([], ['reuse' => true]);
        $this->seedRepair([], ['reuse' => true]);

        $response = $this->fetchCsvResponse(['per_page' => 2]);

        // Without this the endpoint could serve CSV bytes labelled as JSON and
        // every other CSV assertion here would still pass.
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertSame('3', $response->headers->get('X-Total-Count'));
        $this->assertSame('1', $response->headers->get('X-Page'));
        $this->assertSame('2', $response->headers->get('X-Per-Page'));
        $this->assertSame('2', $response->headers->get('X-Last-Page'));
        $this->assertNotEmpty($response->headers->get('X-Max-Updated-At'));
    }

    // ---------------------------------------------------------- redaction

    public function test_problem_text_is_scrubbed(): void
    {
        $this->seedRepair([
            'problem' => '<p>Owner jane@example.com, call 020 7946 0958.</p> '
                .'Serial 123456789012. Part https://example.com/p?gclid=ABC123',
        ]);

        $problem = $this->fetchRecords()[0]['problem'];

        $this->assertStringNotContainsString('jane@example.com', $problem);
        $this->assertStringNotContainsString('020 7946 0958', $problem);
        $this->assertStringNotContainsString('123456789012', $problem);
        $this->assertStringNotContainsString('gclid', $problem);
        $this->assertStringNotContainsString('<p>', $problem);
        $this->assertStringContainsString('https://example.com/p', $problem);
    }

    public function test_problem_is_always_exported_and_always_scrubbed(): void
    {
        // There is no toggle: the column ships on every export and the scrubber
        // always runs. Personal names are not pattern-detectable and are not
        // removed, which is a known limit of the redaction rather than a bug.
        $this->seedRepair(['problem' => 'Reported by jane@example.com, screen cracked']);

        $problem = $this->fetchRecords()[0]['problem'];

        $this->assertNotNull($problem);
        $this->assertStringNotContainsString('jane@example.com', $problem);
        $this->assertStringContainsString('screen cracked', $problem);
    }

    // ------------------------------------------------------------ helpers

    /** @param array<string,mixed> $query */
    private function url(array $query = []): string
    {
        return '/api/v2/repairs?'.http_build_query(
            array_merge(['api_token' => $this->apiToken()], $query)
        );
    }

    /**
     * CategoryFactory::definition() is empty, so every column is supplied here.
     * TestCase::setUp truncates `categories`, so the id only has to avoid the
     * six it re-seeds.
     */
    private function seedCategory(int $id, string $name, bool $powered): void
    {
        Category::factory()->create([
            'idcategories' => $id,
            'name' => $name,
            'revision' => 2,
            'aggregate' => 0,
            'powered' => $powered ? 1 : 0,
        ]);
    }

    /** Backdates the device and both parent rows past the auto-maintained columns. */
    private function backdate(Device $device, string $when): void
    {
        $device->timestamps = false;
        $device->updated_at = $when;
        $device->save();

        $event = Party::findOrFail($device->event);
        $this->touchRow(Party::class, $event->idevents, 'idevents', $when);
        $this->touchRow(Group::class, $event->group, 'idgroups', $when);
    }

    /**
     * `events` and `groups` carry ON UPDATE CURRENT_TIMESTAMP, which only fires
     * when updated_at is left out of the statement, so an explicit value wins.
     *
     * @param class-string<\Illuminate\Database\Eloquent\Model> $model
     */
    private function touchRow(string $model, int $id, string $key, string $when): void
    {
        $model::query()->where($key, $id)->update(['updated_at' => $when]);
    }

    /**
     * An Administrator, because the export is gated on that role the same way
     * UserController::changes is. Every other fixture in this file goes through
     * here, so a regression in the gate fails the whole class rather than one
     * test.
     */
    private function apiToken(): string
    {
        if ($this->apiToken === null) {
            $this->apiToken = User::factory()->administrator()->create()->ensureAPIToken();
        }

        return $this->apiToken;
    }

    /**
     * @param array<string,mixed> $deviceAttributes
     * @param array<string,mixed> $context
     */
    private function seedRepair(array $deviceAttributes = [], array $context = []): Device
    {
        if (! empty($context['reuse'])) {
            $eventId = Party::query()->orderBy('idevents', 'desc')->firstOrFail()->idevents;
        } else {
            // Groups and events are built straight from the factories rather
            // than through TestCase::createGroup/createEvent, which post to the
            // v2 API. This fixture needs an exact country_code, event start and
            // timezone anyway, so the round trip would only be overwritten.
            $group = Group::factory()->create([
                'name' => $context['group'] ?? 'ORDS Group',
                // array_key_exists, not ??, so a test can ask for a null code.
                'country_code' => array_key_exists('country_code', $context) ? $context['country_code'] : 'GB',
                'approved' => $context['approve_group'] ?? true,
                'latitude' => 51.5074,
                'longitude' => -0.1278,
                'location' => 'London',
            ]);

            $startUtc = $context['start_utc'] ?? self::EVENT_START_UTC;

            $event = Party::factory()->create([
                'group' => $group->idgroups,
                'approved' => $context['approve_event'] ?? true,
                'event_start_utc' => $startUtc,
                'event_end_utc' => Carbon::parse($startUtc)->addHours(2)->toDateTimeString(),
                'timezone' => $context['timezone'] ?? 'Europe/London',
            ]);

            $eventId = $event->idevents;
        }

        return Device::create(array_merge([
            'event' => $eventId,
            // 11 is "Desktop computer", seeded by TestCase::setUp.
            'category' => 11,
            'category_creation' => 11,
            'brand' => 'Acme',
            'item_type' => 'Tower PC',
            'age' => 5,
            'problem' => 'Would not power on.',
            'repair_status' => Device::REPAIR_STATUS_FIXED,
        ], $deviceAttributes));
    }

    /**
     * @param array<string,mixed> $query
     * @return array<int,array<string,mixed>>
     */
    private function fetchRecords(array $query = []): array
    {
        $response = $this->getJson($this->url($query));
        $response->assertSuccessful();

        return $response->json('data');
    }

    /**
     * Not TestCase::get, which would try to validate the CSV body against the
     * OpenAPI schema, and not getJson, which would claim an Accept this request
     * does not want. The route is selected by the `format` param either way.
     *
     * @param array<string,mixed> $query
     */
    private function fetchCsvResponse(array $query = []): \Illuminate\Testing\TestResponse
    {
        $response = $this->call('GET', $this->url(array_merge(['format' => 'csv'], $query)));
        $response->assertSuccessful();

        return $response;
    }

    /** @return array<int,array<int,string>> */
    private function fetchCsvRows(): array
    {
        $response = $this->fetchCsvResponse();

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, trim($response->streamedContent()));
        rewind($handle);

        $rows = [];
        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }
}
