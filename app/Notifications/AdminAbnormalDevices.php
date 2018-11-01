<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AdminAbnormalDevices extends Notification
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
                  ->subject('Abnormal number of miscellaneous devices')
                  ->greeting('Hello!')
                  ->line('The event \'' . $this->arr['event_venue'] . '\' has an abnormal number of miscellaneous devices.')
                  ->action('View event', $this->arr['event_url'])
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
          'title' => 'Event has abnormal number of miscellaneous devices:',
          'name' => $this->arr['event_venue'],
          'url' => $this->arr['event_url'],
      ];
    }
}
