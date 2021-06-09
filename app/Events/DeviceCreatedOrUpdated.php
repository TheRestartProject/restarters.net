<?php

namespace App\Events;

use App\Device;
use Illuminate\Queue\SerializesModels;

class DeviceCreatedOrUpdated
{
    use SerializesModels;

    public $iddevices;

    /**
     * Create a new event instance.
     *
     * @param \App\Device $device
     */
    public function __construct(Device $device)
    {
        $this->iddevices = $device->iddevices;
    }
}
