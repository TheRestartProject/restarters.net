<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class NotifyAdminNoDevices extends BaseNotification
{
    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('Recent event with no devices added')
                    ->greeting(__('notifications.greeting', [], $notifiable->language))
                    ->line('Your moderation is needed for \''.$this->arr['event_venue'].'\'.')
                    ->line('No devices have been added against this event.')
                    ->action('View event', $this->arr['event_url'])
                    ->line('If you think this invitation was not intended for you, please discard this email.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'title' => 'Moderation needed on event with no devices:',
            'name' => $this->arr['event_venue'],
            'url' => $this->arr['event_url'],
        ];
    }
}
