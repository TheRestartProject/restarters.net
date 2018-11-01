<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AdminWordPressEditFailureNotification extends Notification
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
                   ->subject('Event WordPress failure')
                   ->greeting('Hello!')
                   ->line('Event \'' . $this->arr['event_venue'] . '\' failed to post to WordPress during an edit to the event.')
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
           'title' => 'Event failed to save to an existing WordPress post:',
           'name' => $this->arr['event_venue'],
           'url' => $this->arr['event_url'],
       ];
     }
}
