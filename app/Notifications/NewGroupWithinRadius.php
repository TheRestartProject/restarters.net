<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class NewGroupWithinRadius extends BaseNotification
{
    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $locale = $notifiable->language;
        return (new MailMessage)
            ->subject(__('notifications.new_group_subject', [], $locale))
            ->greeting(__('notifications.greeting', [], $notifiable->language))
            ->line(
                __('notifications.new_group_line1', [
                    'name' => $this->arr['group_name']
                ], $locale)
            )
            ->action(
                __('notifications.new_group_action', [
                    'name' => $this->arr['group_name']
                ], $locale),
                $this->arr['group_url']
            )
            ->line(
                __('notifications.email_preferences', [
                    'url' => url('/user/edit/' . $notifiable->id)
                ], $locale)
            );
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     */
    public function toArray($notifiable): array
    {
        return [
            'title' => __('notifications.new_group_title', [], $notifiable->language),
            'name' => $this->arr['group_name'],
            'url' => $this->arr['group_url'],
        ];
    }
}
