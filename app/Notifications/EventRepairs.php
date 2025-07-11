<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class EventRepairs extends BaseNotification
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
                __('notifications.event_repairs_subject', [
                    'name' => $this->arr['event_name']
                ], $locale)
            )
            ->greeting(__('notifications.greeting', [], $notifiable->language))
            ->line(
                __('notifications.event_repairs_line1', [
                    'event' => $this->arr['event_name']
                ], $locale)
            )
            ->action(__('notifications.event_repairs_action', [], $locale), url($this->arr['event_url']))
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
        $locale = $notifiable->language;

        return [
            'title' => __('notifications.event_repairs_title', [
                'name' => $this->arr['event_name']
            ],            $locale),
            'name' => $this->arr['event_name'],
            'url' => $this->arr['event_url'],
        ];
    }
}
