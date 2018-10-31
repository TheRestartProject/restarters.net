<?php

namespace App\Listeners;

use App\Events\EditGroupError;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyEditGroupError
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
     * @param  EditGroupError  $event
     * @return void
     */
    public function handle(EditGroupError $event)
    {
        //
    }
}
