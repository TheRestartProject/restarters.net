<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class DeleteEventFromWordpressFailed extends BaseNotification
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
                      ->subject('Failed to delete event from WordPress: '.$this->arr['event_venue'])
                      ->greeting(__('notifications.greeting', [], $notifiable->language))
                      ->line("Event deletion failed for {$this->arr['event_venue']} by {$this->arr['group_name']}.")
                      ->line(' ')
                      ->line('Please find and delete this event manually from WordPress.')
                      ->line(' ')
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
            'title' => "Failed to delete event {$this->arr['event_venue']} by {$this->arr['group_name']} from WordPress",
        ];
    }
}
