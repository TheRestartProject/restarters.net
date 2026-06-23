<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * The groups table filter bar (GroupsTableFilters.vue, shown on the network
 * page) references these translation keys. They were missing from the lang
 * files, so the UI rendered the raw keys (e.g. "groups.search_name_placeholder").
 *
 * Pure PHPUnit test (reads the lang array files directly) so it needs no app
 * boot or database.
 */
class GroupFilterTranslationsTest extends TestCase
{
    public function test_group_table_filter_keys_exist_in_all_locales(): void
    {
        $keys = [
            'search_name_placeholder',
            'search_location_placeholder',
            'search_country_placeholder',
            'search_tags_placeholder',
            'show_filters',
            'hide_filters',
        ];

        foreach (['en', 'fr', 'fr-BE'] as $locale) {
            $path = dirname(__DIR__, 2) . "/lang/$locale/groups.php";
            $this->assertFileExists($path, "Missing lang file for locale '$locale'");

            $translations = require $path;

            foreach ($keys as $key) {
                $this->assertArrayHasKey(
                    $key,
                    $translations,
                    "Missing translation key 'groups.$key' for locale '$locale'"
                );
                $this->assertNotSame('', trim((string) $translations[$key]), "Empty 'groups.$key' for locale '$locale'");
            }
        }
    }
}
