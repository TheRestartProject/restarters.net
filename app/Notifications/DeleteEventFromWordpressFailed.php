<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class DeleteEventFromWordpressFailed extends BaseNotification
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
            ->subject(
                __('notifications.wordpress_delete_event_failed_subject', [
                    'name' => $this->arr['event_venue']
                ], $locale)
            )
            ->greeting(__('notifications.greeting', [], $notifiable->language))
            ->line(
                __('notifications.wordpress_delete_event_failed_line1', [
                    'name' => $this->arr['event_venue'],
                    'group' => $this->arr['group_name']
                ], $locale)
            )
            ->line(' ')
            ->line(__('notifications.wordpress_delete_event_failed_line2', [], $locale))
            ->line(' ')
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
            'title' => __('notifications.wordpress_delete_event_failed_title', [
                'name' => $this->arr['event_venue'],
                'group' => $this->arr['group_name']
            ],            $locale),
        ];
    }
}
