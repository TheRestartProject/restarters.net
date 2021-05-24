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

        // Update the event.
        $event = Party::find($device->event);
        $event->devices_updated_at = \Carbon\Carbon::now();
        $event->save();

        // Update the group.
        $group = Group::find($event->group);
        $group->devices_updated_at = \Carbon\Carbon::now();
        $group->save();
    }
}
