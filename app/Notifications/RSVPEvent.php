<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class RSVPEvent extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */

    protected $arr;
    protected $user;

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
                      ->subject('RSVP Event')
                      ->greeting('Hello!')
                      ->line('A volunteer, ' . $this->arr['user_name'] . ', has sent you an RSVP for \'' . $this->arr['event_venue'] . '\'. You can view the event below to see the latest activity.')
                      ->action('View your event', $this->arr['event_url'])
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
            'title' => $this->arr['user_name'].' is attending your event:',
            'name' => $this->arr['event_venue'],
            'url' => $this->arr['event_url'],
        ];
    }
}
