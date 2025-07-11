<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class AdminWordPressEditEventFailure extends BaseNotification
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
            ->subject(__('notifications.wordpress_edit_event_failure_subject', [], $locale))
            ->greeting(__('notifications.greeting', [], $notifiable->language))
            ->line(
                __('notifications.wordpress_edit_event_failure_line1', [
                    'name' => $this->arr['event_venue']
                ], $locale)
            )
            ->action(__('notifications.view_event', [], $notifiable->language), $this->arr['event_url'])
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
     */
    public function toArray($notifiable): array
    {
        $locale = $notifiable->language;
        return [
            'title' => __('notifications.wordpress_edit_event_failure_title', [], $locale),
            'name' => $this->arr['event_venue'],
            'url' => $this->arr['event_url'],
        ];
    }
}
