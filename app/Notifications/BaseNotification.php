<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function failed($e)
    {
        if (gettype($e) == 'string') {
            \Sentry\captureMessage("Notification failed with $e");
        } else if ($e instanceof \Exception) {
            \Sentry\captureException($e);
        } else {
            \Sentry\captureMessage('Notification failed in an unexpected way ' . gettype($e));
        }
    }
}