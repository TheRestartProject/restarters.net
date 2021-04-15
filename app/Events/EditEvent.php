<?php

namespace App\Events;

use App\Party;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EditEvent
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
