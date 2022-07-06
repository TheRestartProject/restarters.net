<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class AdminModerationEvent extends BaseNotification
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
                      ->subject('New event created: '.$this->arr['event_venue'])
                      ->greeting(__('notifications.greeting', [], $notifiable->language))
                      ->line('A new event has been created: \''.$this->arr['event_venue'].'\'.')
                      ->action('View event', $this->arr['event_url'])
                      ->line('This event might need your moderation, if your network moderates events and it hasn\'t yet been moderated by another administrator.')
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
            'title' => 'New event created:',
            'name' => $this->arr['event_venue'],
            'url' => $this->arr['event_url'],
        ];
    }
}
