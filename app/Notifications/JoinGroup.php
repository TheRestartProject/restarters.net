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
        $subject = 'Invitation from ' . $this->arr['name'] . ' to join ' . $this->arr['group'];
        $introLine = 'You have received this email because you have been invited by ' . $this->arr['name'] . ' to join the community repair group <b>' . $this->arr['group'] . '</b> on restarters.net.';
        $actionText = 'Click to join group';
        $ignoreLine = 'If you think this invitation was not intended for you, please disregard this email.';


        if ( !is_null($this->user) ) { // user is already on the platform
            if ( $this->user->invites == 1 ) { // user has opted in to receive emails
                $mail = (new MailMessage)
                      ->subject($subject)
                      ->greeting('Hello!')
                      ->line($introLine)
                      ->action($actionText, $this->arr['url']);
                if (!is_null($this->arr['message'])) { // host has added a message
                    $mail->line($this->arr['name'] . ' attached this message with the invite:')
                        ->line('')
                        ->line('"' . $this->arr['message'] . '"');
                }
                $mail->line($ignoreLine);

                return $mail;
            }
        } else { // users not yet on the platform
            $mail = (new MailMessage)
                    ->subject($subject)
                    ->greeting('Hello!')
                    ->line($introLine)
                    ->action($actionText, $this->arr['url'])
                  ->line('You can find out more about restarters.net <a href="' . env('APP_URL') . '/about">here</a>.');

            if ( !is_null($this->arr['message']) ) { // host has added a message
                $mail->line($this->arr['name'] . ' attached this message with the invite:')
                     ->line('')
                     ->line('"' . $this->arr['message'] . '"');
            }

            $mail->line($ignoreLine);

            return $mail;
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
