<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RSVPEvent extends Notification implements ShouldQueue
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
          ->subject(__('notifications.rsvp_subject', [
              'name' => $this->arr['user_name']
          ], $locale))
          ->greeting(__('notifications.greeting', [], $locale))
          ->line(__('notifications.rsvp_line1', [
              'user' => $this->arr['user_name'],
              'event' => $this->arr['event_venue']
          ], $locale))
          ->action(__('notifications.rsvp_action', [], $locale), $this->arr['event_url'])
          ->line(__('partials.notification_footer', [
              'url' => url('/user/edit/' . $notifiable->id)
          ]));
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
            'title' => __('notifications.rsvp_title', [
                'name' => $this->arr['user_name']
            ], $notifiable->language),
            'name' => $this->arr['event_venue'],
            'url' => $this->arr['event_url'],
        ];
    }
}
