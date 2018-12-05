<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NotifyHostRSVPInvitesMade extends Notification
{
    use Queueable;

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
          return (new MailMessage)
                      ->subject('Invites have been sent to your event')
                      ->greeting('Hello!')
                      ->line('There have been invites sent out to your event: \'' . $this->event_details['event_venue'] . '\'. URL for reference: \'' . $this->event_details['event_url'] . '\'.')
                      ->line('If you think this email was not intended for you, please discard.')
                      ->line('Thank you.');
    }

    /**
     * Get the event_detailsay representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return event_detailsay
     */
    public function toArray($notifiable)
    {
        return [
          'title' => 'Invites have been sent to your event:',
          'name' => $this->event_details['event_venue'],
          'url' => $this->event_details['event_url'],
        ];
    }
}
