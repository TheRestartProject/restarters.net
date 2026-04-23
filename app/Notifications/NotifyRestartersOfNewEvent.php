<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class NotifyRestartersOfNewEvent extends BaseNotification
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
            ->subject(
                __('notifications.new_event_subject', [
                    'name' => $this->arr['event_group'],
                    'time' => $this->arr['event_start']
                ], $locale)
            )
            ->greeting(__('notifications.greeting', [], $locale))
            ->line(
                __('notifications.new_event_line1', [
                    'name' => $this->arr['event_venue']
                ], $locale)
            )
            ->action(__('notifications.view_event', [], $locale), $this->arr['event_url'])
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
            'title' => __('notifications.new_event_title', [
                'name' => $this->arr['event_group']
            ],            $notifiable->language),
            'name' => $this->arr['event_venue'],
            'url' => $this->arr['event_url'],
        ];
    }
}
