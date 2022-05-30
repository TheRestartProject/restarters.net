<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Don't ShouldQueue yet - we're intentionally only doing this for admin notifications.
class BaseNotification extends Notification
{
    // Wait for 10 minutes between retries.
    public $retryAfter = 600;

    public function retryUntil()
    {
        // Retry for 4 days - bank holiday weekends.
        return now()->addDays(4);
    }

    public function failed($e)
    {
        if (gettype($e) == 'string') {
            \Sentry\captureMessage("Notification failed with $e");
        } else if ($e instanceof \Exception) {
            \Sentry\captureException($e);
        } else {
            \Sentry\captureMessage('Notification in an unexpected way ' . gettype($e));
        }
    }
}