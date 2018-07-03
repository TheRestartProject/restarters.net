<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class JoinGroup extends Notification
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
      if ($this->user !== null) {
        if ($this->user->invites == 1) {
          if (!is_null($this->arr['message'])) {
            return (new MailMessage)
                        ->subject('Group Invitation')
                        ->greeting('Hello!')
                        ->line('You have received this email because you have been invited by ' . $this->arr['name'] . ' to join the Restart Group \'' . $this->arr['group'] . '\'.')
                        ->line('')
                        ->line($this->arr['name'] . ' attached this message with the invite:')
                        ->line('')
                        ->line($this->arr['message'])
                        ->line('')
                        ->action('Join group', $this->arr['url'])
                        ->line('If you think this invitation was not intended for you, please discard this email.');
          } else {
            return (new MailMessage)
                        ->subject('Group Invitation')
                        ->greeting('Hello!')
                        ->line('You have received this email because you have been invited by ' . $this->arr['name'] . ' to join the Restart Group \'' . $this->arr['group'] . '\'.')
                        ->action('Join group', $this->arr['url'])
                        ->line('If you think this invitation was not intended for you, please discard this email.');
          }
        }
      } else {
        if (!is_null($this->arr['message'])) {
          return (new MailMessage)
                      ->subject('Group Invitation')
                      ->greeting('Hello!')
                      ->line('You have received this email because you have been invited by ' . $this->arr['name'] . ' to join the Restart Group \'' . $this->arr['group'] . '\'.')
                      ->line('')
                      ->line($this->arr['name'] . ' attached this message with the invite:')
                      ->line('')
                      ->line($this->arr['message'])
                      ->line('')
                      ->action('Join group', $this->arr['url'])
                      ->line('If you think this invitation was not intended for you, please discard this email.');
        } else {
          return (new MailMessage)
                      ->subject('Group Invitation')
                      ->greeting('Hello!')
                      ->line('You have received this email because you have been invited by ' . $this->arr['name'] . ' to join the Restart Group \'' . $this->arr['group'] . '\'.')
                      ->action('Join group', $this->arr['url'])
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
