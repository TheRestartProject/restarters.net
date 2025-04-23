<?php

namespace App\Events;

use App\Models\Party;
use Illuminate\Queue\SerializesModels;

class EventImagesUploaded
{
    use SerializesModels;

    /**
     * @var Party
     */
    public $party;

    /**
     * @var int
     */
    public $auth_user_id;

    public function __construct(Party $party, int $auth_user_id)
    {
        $this->party = $party;
        $this->auth_user_id = $auth_user_id;
    }
}
