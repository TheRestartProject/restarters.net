<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class AdminModerationEventPhotos extends BaseNotification
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
                __('notifications.new_event_photos_subject', [
                    'event' => $this->arr['event_venue']
                ], $locale)
            )
            ->greeting(__('notifications.greeting', [], $locale))
            ->line(
                __('notifications.moderation_event_photos_line1', [
                    'name' => $this->arr['event_venue']
                ], $notifiable->language)
            )
            ->action(__('notifications.view_event', [], $notifiable->language), $this->arr['event_url'])
            ->line(
                __('notifications.moderation_event_photos_line2', [
                    'name' => $this->arr['event_venue']
                ], $notifiable->language)
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
            'title' => __('notifications.moderation_event_photos_subject', [], $locale),
            'event_id' => $this->arr['event_id'],
            'name' => $this->arr['event_venue'],
            'url' => $this->arr['event_url'],
        ];
    }
}
