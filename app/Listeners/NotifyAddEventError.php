<?php

namespace App\Listeners;

use App\Events\AddEventError;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyAddEventError
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
     * @param  AddEventError  $event
     * @return void
     */
    public function handle(AddEventError $event)
    {
        \Log::info('activation', ['user' => $event->user]);
    }
}
