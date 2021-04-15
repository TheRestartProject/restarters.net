<?php

namespace App\Events;

use App\Group;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

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
