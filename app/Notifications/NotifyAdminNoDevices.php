<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class NotifyAdminNoDevices extends BaseNotification
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
            ->subject(__('notifications.admin_no_devices_subject', [], $locale))
            ->greeting(__('notifications.greeting', [], $locale))
            ->line(
                __('notifications.admin_no_devices_line1', [
                    'name' => $this->arr['event_venue']
                ], $locale)
            )
            ->line(
                __('notifications.admin_no_devices_line2', [
                    'name' => $this->arr['event_venue']
                ], $locale)
            )
            ->action(__('notifications.view_event', [], $locale), $this->arr['event_url']);
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
            'title' => __('notifications.admin_no_devices_title', [], $locale),
            'name' => $this->arr['event_venue'],
            'url' => $this->arr['event_url'],
        ];
    }
}
