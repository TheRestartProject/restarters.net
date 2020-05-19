<?php

namespace App\Events;

use App\User;
use Illuminate\Queue\SerializesModels;

class UserUpdated
{
    use SerializesModels;

    /**
     * @var User
     */
    public $user;

    /**
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
