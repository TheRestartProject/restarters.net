<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ModerationGroup extends Notification
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
                      ->subject('Moderation Needed')
                      ->greeting('Hello!')
                      ->line('Your moderation is needed for \'' . $this->arr['group_name'] . '\'.')
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
            //
        ];
    }
}
