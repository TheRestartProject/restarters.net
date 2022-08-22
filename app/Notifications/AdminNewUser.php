<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class AdminNewUser extends BaseNotification
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
            ->subject(__('notifications.new_user_subject', [], $locale))
            ->greeting(__('notifications.greeting', [], $notifiable->language))
            ->line(
                __('notifications.new_user_line1', [
                    'name' => $this->arr['name']
                ], $notifiable->locale)
            )
            ->action(__('notifications.view_profile', [], $locale), url('/profile/' . $this->arr['id']))
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
            'title' => __('notifications.new_user_title', [], $locale),
            'name' => $this->arr['name'],
            'url' => url('/profile/' . $this->arr['id']),
        ];
    }
}
