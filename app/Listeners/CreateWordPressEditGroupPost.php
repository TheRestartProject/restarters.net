<?php

namespace App\Listeners;

use App\Events\EditGroup;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Notification;
use App\Group;

class CreateWordPressEditGroupPost
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
     * @param  EditGroup  $event
     * @return void
     */
    public function handle(EditGroup $event)
    {
      if ( empty($event->group) )

      try {

        //Yet to be made

        } catch (\Exception $e) {

          $notify_users = FixometerHelper::usersWhoHavePreference('admin-edit-wordpress-group-failure');
          Notification::send($notify_users, new AdminEditWordpressGroupFailure([
            'group_name' => $event->group->name,
            'group_url' => url('/group/edit/'.$event->group->idgroups),
          ]));

      }
    }
}
