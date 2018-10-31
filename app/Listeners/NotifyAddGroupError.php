<?php

namespace App\Listeners;

use App\Events\AddGroupError;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyAddGroupError
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
     * @param  AddGroupError  $event
     * @return void
     */
    public function handle(AddGroupError $event)
    {
        //
    }
}
