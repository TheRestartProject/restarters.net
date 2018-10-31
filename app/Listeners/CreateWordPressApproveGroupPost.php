<?php

namespace App\Listeners;

use App\Events\ApproveGroup;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Notification;
use App\Group;

class CreateWordPressApproveGroupPost
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
     * @param  ApproveGroup  $event
     * @return void
     */
    public function handle(ApproveGroup $event)
    {
      if ( empty($event->group) )

      try {

        //Yet to be made

        } catch (\Exception $e) {

          $notify_users = FixometerHelper::usersWhoHavePreference('admin-approve-wordpress-group-failure');
          Notification::send($notify_users, new AdminApproveWordpressGroupFailure([
            'group_name' => $event->group->name,
            'group_url' => url('/group/edit/'.$event->group->idgroups),
          ]));

      }
    }
}
