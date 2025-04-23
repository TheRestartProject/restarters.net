<?php

namespace App\Events;

use App\Models\Group;
use App\Models\User;
use Illuminate\Queue\SerializesModels;

class UserFollowedGroup
{
    use SerializesModels;

    public $user;
    public $group;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, Group $group)
    {
        $this->user = $user;
        $this->group = $group;
    }
}
