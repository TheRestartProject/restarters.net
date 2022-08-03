<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class AdminWordPressCreateGroupFailure extends BaseNotification
{
    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $locale = $notifiable->language;
        return (new MailMessage)
            ->subject(__('notifications.wordpress_create_group_failure_subject', [], $locale))
            ->greeting(__('notifications.greeting', [], $notifiable->language))
            ->line(
                __('notifications.wordpress_create_group_failure_line1', [
                    'name' => $this->arr['group_name']
                ], $notifiable->locale)
            )
            ->action(__('notifications.view_group', [], $locale), $this->arr['group_url'])
            ->line(
                __('notifications.email_preferences', [
                    'url' => url('/user/edit/' . $notifiable->id)
                ], $notifiable->locale)
            );
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $locale = $notifiable->language;
        return [
            'title' => __('notifications.wordpress_create_group_failure_title', [], $locale),
            'name' => $this->arr['group_name'],
            'url' => $this->arr['group_url'],
        ];
    }
}
