<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class AdminAbnormalDevices extends BaseNotification
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
                  ->subject('Abnormal number of miscellaneous devices')
                  ->greeting(__('notifications.greeting', [], $notifiable->language))
                  ->line('The event \''.$this->arr['event_venue'].'\' has an abnormal number of miscellaneous devices.')
                  ->action('View event', $this->arr['event_url'])
                  ->line(__('notifications.email_preferences', [
                      'url' => url('/user/edit/'.$notifiable->id)
                  ], $notifiable->locale));
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
            'title' => 'Event has abnormal number of miscellaneous devices:',
            'name' => $this->arr['event_venue'],
            'url' => $this->arr['event_url'],
        ];
    }
}
