<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class AdminModerationEvent extends BaseNotification
{
    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject(
                __('notifications.moderation_event_subject', [
                    'name' => $this->arr['event_venue']
                ])
            )
            ->greeting(__('notifications.greeting', [], $notifiable->language))
            ->line(
                __('notifications.moderation_event_line1', [
                    'name' => $this->arr['event_venue']
                ], $notifiable->language)
            )
            ->action(__('notifications.view_event', [], $notifiable->language), $this->arr['event_url'])
            ->line(__('notifications.moderation_event_line2', [], $notifiable->language))
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
        return [
            'title' => __('notifications.moderation_event_subject', [
                'name' => $this->arr['event_venue']
            ]),
            'name' => $this->arr['event_venue'],
            'url' => $this->arr['event_url'],
        ];
    }
}
