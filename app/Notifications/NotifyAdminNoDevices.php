<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NotifyAdminNoDevices extends Notification
{
    use Queueable;

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
         return (new MailMessage)
                     ->subject('Moderation Needed')
                     ->greeting('Hello!')
                     ->line('Your moderation is needed for \'' . $this->arr['event_venue'] . '\'.')
                     ->line('No devices have been added against this event.')
                     ->action('View event', $this->arr['event_url'])
                     ->line('If you think this invitation was not intended for you, please discard this email.');
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
           'title' => 'Moderation needed on event with no devices:',
           'name' => $this->arr['event_venue'],
           'url' => $this->arr['event_url'],
       ];
     }
}
