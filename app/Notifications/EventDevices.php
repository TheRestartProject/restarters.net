<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class EventDevices extends Notification
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
      if ($notifiable !== null) {
        if ($notifiable->invites == 1) {

          return (new MailMessage)
                      ->subject('Contribute Devices')
                      ->greeting('Hello!')
                      ->line('Thank you for hosting the event \'' . $this->arr['event_venue'] . '\', please help us outline what devices were bought to the event and the status of their repair. This will help us improve the quality of our data.')
                      ->action('Contribute data', $this->arr['event_url'])
                      ->line('If you would like to stop receiving these emails, please visit <a href="' . $this->arr['preferences'].'/'.$notifiable->id . '">your preferences</a> on your account.');
        }
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
