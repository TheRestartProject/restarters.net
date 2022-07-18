<?php

namespace App\Listeners;

use Carbon\Carbon;
use Cookie;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

class LogSuccessfulLogin
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Handle the event.
     *
     * @param  Login  $event
     * @return void
     */
    public function handle(Login $event)
    {
        $user = $event->user;

        $user->last_login_at = Carbon::now()->toDateTimeString();
        $user->number_of_logins += 1;

        $user->save();
    }
}
