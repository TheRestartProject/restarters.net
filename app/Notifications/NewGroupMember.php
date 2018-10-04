<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NewGroupMember extends Notification
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
      if ($this->user !== null) {
        if ($this->user->invites == 1) {
          return (new MailMessage)
                      ->subject('New Group Member')
                      ->greeting('Hello!')
                      ->line('A new volunteer, ' . $this->arr['user_name'] . ', has joined your group \'' . $this->arr['group_name'] . '\'.')
                      ->action('Go to group', $this->arr['group_url'])
                      ->line('If you would like to stop receiving these emails, please visit <a href="' . $this->arr['preferences'] . '">your preferences</a> on your account.');
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
          'title' => 'A new volunteer has joined (\'' . $this->arr['group_name'] . '\':',
          'name' => $this->arr['user_name'],
          'url' => '',
        ];
    }
}
