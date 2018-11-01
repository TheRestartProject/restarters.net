<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Group;

class ApproveGroup
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $group;
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
