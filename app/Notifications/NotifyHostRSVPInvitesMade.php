<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotifyHostRSVPInvitesMade extends Notification implements ShouldQueue
{
    use Queueable;

    protected $event_details;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($event_details)
    {
        $this->event_details = $event_details;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return event_detailsay
     */
    public function via($notifiable)
    {
        if ($notifiable->invites == 1) {
            return ['mail', 'database'];
        }

        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $locale = $notifiable->language;
        return (new MailMessage)
                      ->subject(__('notifications.invites_made_subject', [], $locale))
                      ->greeting(__('notifications.greeting', [], $notifiable->language))
                      ->line(__('notifications.invites_made_line1', [
                          'name' => $this->event_details['event_venue'],
                          'url' => $this->event_details['event_url']
                      ], $notifiable->language))
                      ->line(__('notifications.invites_made_line2', [], $locale))
                      ->line(__('notifications.thanks', [], $locale));
    }

    /**
     * Get the event_details representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return event_detailsay
     */
    public function toArray($notifiable)
    {
        return [
            'title' => __('notifications.invites_made_title', [], $notifiable->language),
            'name' => $this->event_details['event_venue'],
            'url' => $this->event_details['event_url'],
        ];
    }
}
