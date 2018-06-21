<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class JoinEvent extends Notification
{
    use Queueable;

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
        if (!is_null($this->arr['message'])) {
          return (new MailMessage)
                      ->subject('Event Invitation')
                      ->greeting('Hello!')
                      ->line('You have received this email because you have been invited by ' . $this->arr['name'] . ' to join the ' . $this->arr['group'] . ' at an upcoming event:')
                      ->line('You can turn up on the day, or if you would prefer you can also create an account with us and RSVP online.')
                      ->line('')
                      ->line($this->arr['name'] . ' attached this message with the invite:')
                      ->line('')
                      ->line($this->arr['message'])
                      ->line('')
                      ->action('RSVP now', $this->arr['url'])
                      ->line('If you think this invitation was not intended for you, please discard this email.');
        } else {
          return (new MailMessage)
                      ->subject('Event Invitation')
                      ->greeting('Hello!')
                      ->line('You have received this email because you have been invited by ' . $this->arr['name'] . ' to join the ' . $this->arr['group'] . ' at an upcoming event:')
                      ->line('You can turn up on the day, or if you would prefer you can also create an account with us and RSVP online.')
                      ->action('RSVP now', $this->arr['url'])
                      ->line('If you think this invitation was not intended for you, please discard this email.');
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
            //
        ];
    }
}
