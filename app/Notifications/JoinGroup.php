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
    protected $test;
    public function __construct(array $test)
    {
        $this->test = $test;
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
        // $url = '';
        $name = $this->test[0];

        return (new MailMessage)
                    ->subject('Group Invitation')
                    ->greeting('Hello!')
                    ->line('You have received this email because you have been invited by ' . $name . ' to join the Restart Group \'' . $this->test[1] . '\'.')
                    ->action('Join group', url('/'))
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
            //
        ];
    }
}
