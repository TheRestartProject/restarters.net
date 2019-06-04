<?php

namespace App\Listeners;

use App\Events\UserDeleted;
use App\Helpers\FixometerHelper;
use App\Notifications\AdminUserDeleted;
use Illuminate\Support\Facades\Notification;

class SendAdminUserDeletedNotification
{
    /**
     * @param UserDeleted $event
     */
    public function handle(UserDeleted $event)
    {
        $notify_users = FixometerHelper::usersWhoHavePreference('admin-user-deleted');

        Notification::send($notify_users, new AdminUserDeleted([
            'id' => $event->user->id,
            'name' => $event->user->name,
        ]));
    }
}