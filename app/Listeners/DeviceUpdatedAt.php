<?php

namespace App\Listeners;

use App\Device;
use App\Events\DeviceCreatedOrUpdated;
use App\Group;
use App\Party;

class DeviceUpdatedAt
{
    /**
     * Handle the event.
     *
     * @param  DeviceCreatedOrUpdated  $event
     * @return void
     */
    public function handle(DeviceCreatedOrUpdated $event)
    {
        // We've been passed a device id, and we want to record in the event and group that the devices have been
        // updated.
        $device = Device::find($event->iddevices);

        // Use a local for the time to avoid a window where the times could be different.
        $now = \Carbon\Carbon::now();

        // Update the event.
        $event = Party::find($device->event);
        $event->devices_updated_at = $now;
        $event->save();

        // Update the group.
        $group = Group::find($event->group);
        $group->devices_updated_at = $now;
        $group->save();
    }
}
