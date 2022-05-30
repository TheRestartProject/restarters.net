<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Don't ShouldQueue yet - we're intentionally only doing this for admin notifications.
class BaseNotification extends Notification
{
    public function failed($e)
    {
        if (app()->bound('sentry')) {
            if (gettype($e) == 'string') {
                app('sentry')->captureMessage("Notification failed with $e");
            } else if ($e instanceof \Exception) {
                app('sentry')->captureException($e);
            } else {
                app('sentry')->captureMessage('Notification in an unexpected way ' . gettype($e));
            }
        }
    }
}