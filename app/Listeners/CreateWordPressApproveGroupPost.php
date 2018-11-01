<?php

namespace App\Listeners;

use App\Events\ApproveGroup;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Notification;
use App\Notifications\AdminApproveGroupNotification;
use App\Group;
use FixometerHelper;

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
      // Set event variable
      $theGroup = Group::find($event->group->idgroups);
      $data = $event->data;
      
      if ( !empty($theGroup) ){

        try {

        //Yet to be made

        } catch (\Exception $e) {

          $notify_users = FixometerHelper::usersWhoHavePreference('admin-approve-wordpress-group-failure');
          Notification::send($notify_users, new AdminApproveGroupNotification([
            'group_name' => $event->group->name,
            'group_url' => url('/group/edit/'.$event->group->idgroups),
          ]));

      }
    }
}
