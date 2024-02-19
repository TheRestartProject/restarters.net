<?php

namespace App\Events;

use App\Group;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EditGroup
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $group;
    public $data;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Group $group, $data)
    {
        $this->group = $group;
        $this->data = $data;
    }
}
