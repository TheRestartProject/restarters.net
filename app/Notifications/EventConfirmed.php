<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventConfirmed extends Notification implements ShouldQueue
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
        $locale = $notifiable->language;
        return (new MailMessage)
                    ->subject(__('notifications.event_confirmed_subject', [], $locale))
                    ->greeting(__('notifications.greeting'))
                    ->line(__('notifications.event_confirmed_line1', [
                        'url' => $this->arr[0]
                    ], $locale))
                    ->action(__('notifications.event_confirmed_view', [], $locale), url('/'))
                    ->line(__('notifications.event_confirmed_line2', [
                        'url' => $this->arr[1]
                    ], $locale));
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
            'title' => __('notifications.event_confirmed_subject', [], $locale),
            'event_id' => $this->arr['event_id'],
            'name' => $this->arr['event_venue'],
            'url' => $this->arr['event_url'],
        ];
    }
}
