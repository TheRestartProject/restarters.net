<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AdminModerationGroup extends Notification
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
                  ->subject('Moderation Needed')
                  ->greeting('Hello!')
                  ->line('Your moderation is needed for \'' . $this->arr['group_name'] . '\'.')
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
          'title' => 'The following group needs moderating:',
          'name' => $this->arr['group_name'],
          'url' => $this->arr['group_url'],
      ];
    }
}
