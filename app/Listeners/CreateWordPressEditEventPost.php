<?php

namespace App\Listeners;

use App\Events\EditEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Notification;
use App\Party;

class CreateWordPressEditEventPost
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
     * @param  EditEvent  $event
     * @return void
     */
    public function handle(EditEvent $event)
    {
      if ( empty($event->group) )

      $theParty = Party::find($event->event->idgroups);

      try {

        //Yet to be made

        } catch (\Exception $e) {


          $notify_users = FixometerHelper::usersWhoHavePreference('admin-edit-wordpress-event-failure');
          Notification::send($notify_users, new AdminEditWordpressEventFailure([
              'event_venue' => $theParty->venue,
              'event_url' => url('/party/edit/'.$theParty->idevents),
          ]));

      }
    }
}
