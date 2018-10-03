<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ReviewNotes extends Notification
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
        if ($notifiable == null)
            return [];

        $channels = [];
        if ($notifiable->invites) {
            $channels[] = 'mail';
        }

        return $channels;
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
                      ->subject('A repair has been marked as suitable for the wiki')
                      ->greeting('Hello!')
                      ->line($this->arr['current_user_name'] . ' has marked a repair as suitable for the wiki.')
                      ->action('View device', $this->arr['device_url'])
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
