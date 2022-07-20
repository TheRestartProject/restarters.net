<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class AdminModerationGroup extends BaseNotification
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
                  ->subject('New group created: '.$this->arr['group_name'])
                  ->greeting(__('notifications.greeting', [], $notifiable->language))
                  ->line('A new group has been created: \''.$this->arr['group_name'].'\'.')
                  ->action('View group', $this->arr['group_url'])
                  ->line('This group might need your moderation, if it hasn\'t yet been moderated by another administrator.')
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
            'title' => 'New group created:',
            'name' => $this->arr['group_name'],
            'url' => $this->arr['group_url'],
        ];
    }
}
