<?php

namespace App\Events;

use App\Models\Party;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EventDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $repairEvent;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Party $repairEvent)
    {
        $this->repairEvent = $repairEvent;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
