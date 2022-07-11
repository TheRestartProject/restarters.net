<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class EventRepairs extends BaseNotification
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
        if ($notifiable->invites == 1) {
            return ['database', 'mail'];
        }

        return ['database'];
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
            ->subject(__('notifications.event_repairs_subject', [
                'name' => $this->arr['event_name']
            ], $locale))
            ->greeting(__('notifications.greeting', [], $notifiable->language))
            ->line(__('notifications.event_repairs_line1', [
                'event' => $this->arr['event_name']
            ], $locale))
            ->action(__('notifications.event_repairs_action', [], $locale), url($this->arr['event_url']))
            ->line(__('notifications.email_preferences', [
                'url' => url('/user/edit/'.$notifiable->id)
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
            'title' => __('notifications.event_repairs_title', [
                'name' => $this->arr['event_name']
            ], $locale),
            'name' => $this->arr['event_name'],
            'url' => $this->arr['event_url'],
        ];
    }
}
