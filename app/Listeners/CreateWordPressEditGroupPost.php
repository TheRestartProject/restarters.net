<?php

namespace App\Listeners;

use App\Events\EditGroup;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Notification;
use App\Notifications\AdminEditGroupNotification;
use App\Group;
use FixometerHelper;

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
      // Set event variable
      $theGroup = Group::find($event->group->idgroups);
      $data = $event->data;
      
      if ( !empty($theGroup) ){

        try {

        //Yet to be made

        } catch (\Exception $e) {

          $notify_users = FixometerHelper::usersWhoHavePreference('admin-edit-wordpress-group-failure');
          Notification::send($notify_users, new AdminEditGroupNotification([
            'group_name' => $theGroup->name,
            'group_url' => url('/group/edit/'.$theGroup->idgroups),
          ]));

      }
    }
}
