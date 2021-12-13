<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GroupConfirmed extends Notification implements ShouldQueue
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
        if ($this->user !== null && $this->user->invites == 1) {
            $locale = $notifiable->language;

            return (new MailMessage)
                ->subject(__('notifications.group_confirmed_subject', [], $locale))
                ->greeting(__('notifications.greeting', [], $notifiable->language))
                ->line(__('notifications.group_confirmed_line1', [
                    'name' => $this->arr['group_name']
                ], $locale))
                ->action(__('notifications.group_confirmed_action', [], $locale), $this->arr['group_url'])
                ->line(__('notifications.email_preferences', [
                    'url' => $this->arr['preferences']
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
        return [
            'name' => $this->arr['group_name'],
            'url' => $this->arr['group_url']
        ];
    }
}
