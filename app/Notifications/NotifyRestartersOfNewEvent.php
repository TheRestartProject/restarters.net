<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NotifyRestartersOfNewEvent extends Notification
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
        return ['mail', 'database'];
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
                       ->subject('There is a New Event')
                       ->greeting('Hello!')
                       ->line('There has been a new Event added to your Group: \'' . $this->arr['event_venue'] . '\'.')
                       ->action('View event', $this->arr['event_url'])
                       ->line('If you think this invitation was not intended for you, please discard this email.');
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
          'title' => 'A new Event has been Created:',
          'name' => $this->arr['event_venue'],
          'url' => $this->arr['event_url'],
      ];
    }
}
