<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class EventDevices extends BaseNotification
{
    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        if ($notifiable !== null && $notifiable->invites == 1)
        {
            $locale = $notifiable->language;
            return (new MailMessage)
                ->subject(__('notifications.event_devices_subject', [], $locale))
                ->greeting(__('notifications.greeting', [], $locale))
                ->line(
                    __('notifications.event_devices_line1', [
                        'event' => $this->arr['event_venue']
                    ], $locale)
                )
                ->action(__('notifications.event_devices_action', [], $locale), $this->arr['event_url'])
                ->line(
                    __('notifications.email_preferences', [
                        'url' => url('/user/edit/' . $notifiable->id)
                    ], $locale)
                );
        }
        // Return empty mail message when conditions aren't met
        return new MailMessage;
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
            'title' => __('notifications.event_devices_title', [], $locale),
            'name' => $this->arr['event_venue'],
        ];
    }
}
