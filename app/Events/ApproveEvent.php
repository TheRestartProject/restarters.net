<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Party;

class ApproveEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $party;
    /**
     * Create a new event instance.
     *
     * @return void
     */
     public function __construct(Party $party, $data)
     {
         $this->party = $party;
         $this->data = $data;
     }
}
