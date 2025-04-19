<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class AdminUserDeleted extends BaseNotification
{
    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        $locale = $notifiable->language;
        return (new MailMessage)
            ->subject(
                __('notifications.user_deleted_subject', [], $locale))
                    ->greeting(__('notifications.greeting', [], $notifiable->language))
                    ->line(
                        __('notifications.user_deleted_line1', [
                            'name' => $this->arr['name']
                        ], $notifiable->locale)
                    )
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
    public function toArray($notifiable): array
    {
        $locale = $notifiable->language;
        return [
            'title' => __('notifications.user_deleted_title', [], $locale),
            'name' => $this->arr['name'],
        ];
    }
}
