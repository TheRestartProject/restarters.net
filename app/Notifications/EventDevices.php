<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class EventDevices extends BaseNotification
{
    use Queueable;

    protected $arr;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($arr)
    {
        $this->arr = $arr;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        if ($notifiable !== null && $notifiable->invites == 1) {
            $locale = $notifiable->language;
            return (new MailMessage)
                  ->subject(__('notifications.event_devices_subject', [], $locale))
                  ->greeting(__('notifications.greeting', [], $locale))
                  ->line(__('notifications.event_devices_line1', [
                      'event' => $this->arr['event_venue']
                    ], $locale))
                  ->action(__('notifications.event_devices_action', [], $locale), $this->arr['event_url'])
                  ->line(__('notifications.email_preferences', [
                      'url' => url('/user/edit/'.$notifiable->id)
                  ], $locale));
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $locale = $notifiable->language;
        return [
            'title' => __('notifications.event_devices_title', [], $locale),
            'name' => $this->arr['event_venue'],
        ];
    }
}
