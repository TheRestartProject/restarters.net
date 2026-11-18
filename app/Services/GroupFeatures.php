<?php

namespace App\Services;

use App\Group;
use App\Network;

/**
 * Features a group can switch on or off, and which setting applies (RES-2063).
 *
 * A network sets each feature to "on" or "off" (always, for all its groups), or "default_on" or "default_off"
 * (a default its groups can change), or leaves it unset. A group sets each feature to true or false, or leaves
 * it unset. What applies, in order: a network's always setting, the group's own setting, a network's default,
 * the feature's own default. When networks disagree, on wins.
 *
 * Settings are stored as JSON on groups and networks, holding only what someone has set. Switches change what
 * is shown; the API accepts every field whatever the switches say.
 */
class GroupFeatures
{
    // Feature key => its own default.
    public const FEATURES = [
        'item_weight' => true,
        'custom_fields' => false,
    ];

    public const NETWORK_VALUES = ['on', 'off', 'default_on', 'default_off'];

    /**
     * Every feature with what applies and why:
     * enabled, source (network, group, network_default or default), locked (a network decides),
     * group_setting (the group's own, or null), inherited (what applies without the group's own setting) and
     * set_by (the names of the networks behind a network or network_default source).
     */
    public static function resolve(Group $group): array
    {
        $own = is_array($group->features) ? $group->features : [];
        $result = [];

        foreach (self::FEATURES as $key => $default) {
            $byValue = array_fill_keys(self::NETWORK_VALUES, []);

            foreach ($group->networks as $network) {
                $value = self::networkSettings($network)[$key];

                if ($value !== null) {
                    $byValue[$value][] = $network->name;
                }
            }

            $groupSetting = isset($own[$key]) && is_bool($own[$key]) ? $own[$key] : null;

            if ($byValue['default_on'] || $byValue['default_off']) {
                $inherited = (bool) $byValue['default_on'];
                $inheritedFrom = $byValue['default_on'] ?: $byValue['default_off'];
            } else {
                $inherited = $default;
                $inheritedFrom = [];
            }

            if ($byValue['on'] || $byValue['off']) {
                $enabled = (bool) $byValue['on'];
                $source = 'network';
                $setBy = $byValue['on'] ?: $byValue['off'];
            } elseif ($groupSetting !== null) {
                $enabled = $groupSetting;
                $source = 'group';
                $setBy = [];
            } else {
                $enabled = $inherited;
                $source = $inheritedFrom ? 'network_default' : 'default';
                $setBy = $inheritedFrom;
            }

            $result[$key] = [
                'enabled' => $enabled,
                'source' => $source,
                'locked' => $source === 'network',
                'group_setting' => $groupSetting,
                'inherited' => $inherited,
                'set_by' => array_values($setBy),
            ];
        }

        return $result;
    }

    /**
     * @return array<string, bool>
     */
    public static function enabled(Group $group): array
    {
        return array_map(fn ($feature) => $feature['enabled'], self::resolve($group));
    }

    public static function isEnabled(Group $group, string $key): bool
    {
        return self::resolve($group)[$key]['enabled'];
    }

    /**
     * Every feature with the network's setting, or null where it has none.
     *
     * @return array<string, ?string>
     */
    public static function networkSettings(Network $network): array
    {
        $own = is_array($network->features) ? $network->features : [];
        $result = [];

        foreach (array_keys(self::FEATURES) as $key) {
            $value = $own[$key] ?? null;
            $result[$key] = in_array($value, self::NETWORK_VALUES, true) ? $value : null;
        }

        return $result;
    }

    /**
     * Apply changes to stored settings: a null value removes the setting.
     */
    public static function apply(?array $stored, array $changes): array
    {
        $stored = $stored ?? [];

        foreach ($changes as $key => $value) {
            if ($value === null) {
                unset($stored[$key]);
            } else {
                $stored[$key] = $value;
            }
        }

        return $stored;
    }
}
