<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AdminNewUser extends BaseNotification implements ShouldQueue
{
    use Queueable;

    protected $arr;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($arr)
    {
        $this->arr = $arr;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        if ($notifiable->invites == 1) {
            return ['mail', 'database'];
        }

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
                      ->subject('New User Registration')
                      ->greeting(__('notifications.greeting', [], $notifiable->language))
                      ->line('A new user "'.$this->arr['name'].'" has joined the Restarters community.')
                      ->action('View profile', url('/profile/'.$this->arr['id']))
                      ->line(__('notifications.email_preferences', [
                          'url' => url('/user/edit/'.$notifiable->id)
                      ], $notifiable->locale));
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
            'title' => 'New user has joined the community:',
            'name' => $this->arr['name'],
            'url' => url('/profile/'.$this->arr['id']),
        ];
    }
}
