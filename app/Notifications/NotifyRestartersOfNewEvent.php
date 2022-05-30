<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class NotifyRestartersOfNewEvent extends BaseNotification
{
    use Queueable;

    protected $arr;
    protected $user;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($arr, $user = null)
    {
        $this->arr = $arr;
        $this->user = $user;
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
            return ['mail', 'database'];
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
                    ->subject(__('notifications.new_event_subject', [
                        'name' => $this->arr['event_group']
                    ], $locale))
                    ->greeting(__('notifications.greeting', [], $locale))
                    ->line(__('notifications.new_event_line1', [
                        'name' => $this->arr['event_venue']
                    ], $locale))
                    ->action(__('notifications.new_event_action', [], $locale), $this->arr['event_url'])
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
        return [
            'title' => __('notifications.new_event_title', [
                'name' => $this->arr['event_group']
            ], $notifiable->language),
            'name' => $this->arr['event_venue'],
            'url' => $this->arr['event_url'],
        ];
    }
}
