<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $arr;
    protected $user;

    public function __construct($arr, $user = null)
    {
        $this->arr = $arr;
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        // If user being notified has opted in to receive emails.
        if ($notifiable->invites == 1) {
            return ['mail', 'database'];
        }

        return ['database'];
    }

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