<?php

namespace App\Listeners;

use App\Events\EditEventError;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyEditEventError
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  EditEventError  $event
     * @return void
     */
    public function handle(EditEventError $event)
    {
        //
    }
}
