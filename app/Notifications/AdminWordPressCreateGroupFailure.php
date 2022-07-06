<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class AdminWordPressCreateGroupFailure extends BaseNotification
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
                  ->subject('Group WordPress failure')
                  ->greeting(__('notifications.greeting', [], $notifiable->language))
                  ->line('Error creating group page for \''.$this->arr['group_name'].'\' on WordPress.')
                  ->action('View group', $this->arr['group_url'])
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
            'title' => 'Group failed to create a new WordPress post:',
            'name' => $this->arr['group_name'],
            'url' => $this->arr['group_url'],
        ];
    }
}
