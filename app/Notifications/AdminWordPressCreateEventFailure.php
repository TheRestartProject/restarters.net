<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class AdminWordPressCreateEventFailure extends BaseNotification
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
                  ->subject('Event WordPress failure')
                  ->greeting(__('notifications.greeting', [], $notifiable->language))
                  ->line('Event \''.$this->arr['event_venue'].'\' failed to create a WordPress post during admin approval.')
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
            'title' => 'Event failed to create a new WordPress post:',
            'name' => $this->arr['event_venue'],
            'url' => $this->arr['event_url'],
        ];
    }
}
