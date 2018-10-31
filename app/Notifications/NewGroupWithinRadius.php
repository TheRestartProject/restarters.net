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
                       ->subject('New Group nearby')
                       ->greeting('Hello!')
                       ->line('A new group has appeared nearby \'' . $this->arr['group_name'] . '\'.')
                       ->action('View group', $this->arr['group_url'])
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
           'title' => 'New Group nearby:',
           'name' => $this->arr['group_name'],
           'url' => $this->arr['group_url'],
       ];
     }
}
