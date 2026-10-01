<?php

namespace App\Services;

use App\Device;
use App\UserGroups;
use DB;

/**
 * The repair records as a CSV file: one row per item, with its impact.
 */
class RepairDataExport
{
    /**
     * The devices a user can see - the same rules as User::userCanSeeEvent(), but applied in the query so that we
     * don't have to look up each event and group in turn.  No user means what anyone can see.
     */
    public function query($viewer = null, $idevents = null, $idgroups = null)
    {
        return Device::with([
            'deviceCategory',
            'deviceEvent.theGroup',
        ])
            ->join('events', 'events.idevents', '=', 'devices.event')
            ->join('groups', 'groups.idgroups', '=', 'events.group')
            ->whereNull('events.deleted_at')
            ->when($idevents != null, function ($query) use ($idevents) {
                return $query->where('events.idevents', $idevents);
            })
            ->when($idgroups != null, function ($query) use ($idgroups) {
                return $query->where('events.group', $idgroups);
            })
            ->when(! $viewer || ! $viewer->hasRole('Administrator'), function ($query) use ($viewer) {
                $extraGroups = $this->groupsWithUnapprovedEventsVisibleTo($viewer);

                return $query->where(function ($query) use ($extraGroups) {
                    $query->where(function ($query) {
                        $query->where('events.approved', true)
                            ->where('groups.approved', true);
                    })->orWhereIn('events.group', $extraGroups);
                });
            })
            ->select('devices.*', 'groups.name AS group_name');
    }

    /**
     * Write the devices a query finds to an open file.
     */
    public function write($devices, $file): void
    {
        $displacementFactor = Device::getDisplacementFactor();
        $eEmissionRatio = \App\Helpers\LcaStats::getEmissionRatioPowered();
        $uEmissionratio = \App\Helpers\LcaStats::getEmissionRatioUnpowered();

        // We can't put accented characters into a CSV file, so flatten them.
        // Use //TRANSLIT//IGNORE to handle characters that can't be transliterated on
        // servers with older glibc (e.g. 2.27) and POSIX locale, which lack transliteration
        // tables for certain Unicode characters like emdash (—). Without //IGNORE, iconv
        // throws "Detected an illegal character in input string" on such systems.
        $columns = [
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('devices.item_type_short')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('devices.category')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('devices.brand')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('devices.model')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('devices.title_assessment')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('devices.repair_status')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('devices.spare_parts')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('events.event')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('groups.group')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('events.event_date')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('events.stat-7')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('events.stat-6')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', ucfirst(__('devices.title_powered'))),
        ];

        fputcsv($file, $columns);

        // Work through the devices in chunks so that the whole dataset isn't held in memory at once.
        foreach ($devices->lazyById(1000, 'devices.iddevices', 'iddevices') as $device) {
            set_time_limit(60);

            $wasteImpact = 0;
            $co2Diverted = 0;

            if ($device->isFixed()) {
                if ($device->deviceCategory->powered) {
                    $wasteImpact = $device->eWasteDiverted();
                    $co2Diverted = $device->eCo2Diverted($eEmissionRatio, $displacementFactor);
                } else {
                    $wasteImpact = $device->uWasteDiverted();
                    $co2Diverted = $device->uCo2Diverted($uEmissionratio, $displacementFactor);
                }
            }

            fputcsv($file, self::csvSafeRow([
                $device->item_type,
                $device->deviceCategory->name,
                $device->brand,
                $device->model,
                $device->problem,
                $device->getRepairStatus(),
                $device->getSpareParts(),
                $device->deviceEvent->getEventName(),
                $device->deviceEvent->theGroup->name,
                $device->deviceEvent->getFormattedLocalStart('Y-m-d'),
                $wasteImpact,
                $co2Diverted,
                $device->deviceCategory->powered ? 'Powered' : 'Unpowered',
            ]));
        }
    }

    /**
     * Groups whose events a user can see even when the event or group isn't approved: those they host, and those in
     * networks they coordinate.
     */
    private function groupsWithUnapprovedEventsVisibleTo($user): array
    {
        if (! $user) {
            return [];
        }

        $groups = [];

        if ($user->hasRole('Host')) {
            $groups = UserGroups::where('user', $user->id)
                ->where('role', \App\Role::HOST)
                ->pluck('group')
                ->all();
        }

        $networks = $user->networks->pluck('id');

        if ($networks->count()) {
            $groups = array_merge($groups, DB::table('group_network')
                ->whereIn('network_id', $networks)
                ->pluck('group_id')
                ->all());
        }

        return array_values(array_unique($groups));
    }

    /**
     * Spreadsheets treat a cell starting with =, +, - or @ as a formula, so prefix those
     * with an apostrophe.  Device fields are free text entered at events.
     */
    public static function csvSafeRow(array $row)
    {
        return array_map(function ($value) {
            if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
                return "'".$value;
            }

            return $value;
        }, $row);
    }
}
