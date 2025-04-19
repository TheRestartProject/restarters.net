<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class AdminModerationGroup extends BaseNotification
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
                __('notifications.moderation_group_subject', [
                    'name' => $this->arr['group_name']
                ], $locale)
            )
            ->greeting(__('notifications.greeting', [], $notifiable->language))
            ->line(
                __('notifications.moderation_group_line1', [
                    'name' => $this->arr['group_name']
                ], $notifiable->language)
            )
            ->action('View group', $this->arr['group_url'])
            ->line(__('notifications.moderation_group_line2', [], $notifiable->language))
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
            'title' => __('notifications.moderation_group_title', [], $locale),
            'name' => $this->arr['group_name'],
            'url' => $this->arr['group_url'],
        ];
    }
}
