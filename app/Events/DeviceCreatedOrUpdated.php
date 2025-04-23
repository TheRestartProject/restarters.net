<?php

namespace App\Events;

use App\Models\Device;
use Illuminate\Queue\SerializesModels;

class DeviceCreatedOrUpdated
{
    use SerializesModels;

    public $iddevices;

    /**
     * Create a new event instance.
     */
    public function __construct(Device $device)
    {
        $this->iddevices = $device->iddevices;
    }
}
