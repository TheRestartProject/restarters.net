<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NewGroupWithinRadius extends Notification
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

        if( $notifiable->invites == 1 )
          return ['mail', 'database'];

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
         return (new MailMessage)
                     ->subject('A new Restart Group near to you')
                     ->greeting('Hello!')
                     ->line('A new group \'' . $this->arr['group_name'] . '\' has appeared near your location.')
                     ->action('View group', $this->arr['group_url'])
                     ->line('If you would like to stop receiving these emails, please visit <a href="' . url('/user/edit/'.$notifiable->id) . '">your preferences</a> on your account.');
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
           'title' => 'A new Restart Group available near you:',
           'name' => $this->arr['group_name'],
           'url' => $this->arr['group_url'],
       ];
     }
}
