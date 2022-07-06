<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class AdminUserDeleted extends BaseNotification
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
                      ->subject('User Deleted')
                      ->greeting(__('notifications.greeting', [], $notifiable->language))
                      ->line('The user "'.$this->arr['name'].'" has deleted their Restarters account.')
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
            'title' => 'User has deleted their account:',
            'name' => $this->arr['name'],
        ];
    }
}
