<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JoinEvent extends Notification implements ShouldQueue
{
    use Queueable;

    protected $arr;
    protected $user;

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
        if ($notifiable == null) {
            return [];
        }

        $channels = [];

        // If we're sending to a registered user, only send by mail channel if opted in to emails.
        if ($this->user !== null) {
            // Note: in future we will also add the 'database' channel here.
            if ($notifiable->invites) {
                $channels[] = 'mail';
            }
        } else { // Unregistered users
            // We can only send by email.
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $subject = 'You\'ve been invited to an upcoming '.$this->arr['group'].' event';
        $greeting = 'Hello!';
        $introLine = 'You have received this email because you have been invited by '.$this->arr['name'].' to join '.$this->arr['group'].' at an upcoming event:';
        $eventDetailsTable = '<table style="margin-left:120px; color:black">
                                    <tr>
                                      <td>Group:</td>
                                      <td>'.$this->arr['group'].'</td>
                                    </tr>
                                    <tr>
                                    <td>Date:</td>
                                    <td>'.$this->arr['event']->getEventDate('D jS M Y').'</td>
                                  </tr>
                                  <tr>
                                    <td>Time:</td>'.
                                    '<td>'.$this->arr['event']->getEventStartEnd().'</td>
                                  </tr>
                                    <tr>
                                    <td>Location:</td>
                                    <td>'.$this->arr['event']->location.'</td>
                                  </tr>
                            </table>';
        $ignoreLine = 'If you think this invitation was not intended for you, please disregard this email.';

        if ($this->user !== null) { // existing user
            if ($this->user->invites == 1) { // opted in to emails
                $mail = (new MailMessage)
                      ->subject($subject)
                      ->greeting($greeting)
                      ->line($introLine)
                      ->line('')
                      ->line($eventDetailsTable)
                      ->line('<br>');

                // Add message from sender if they left one
                if ( ! is_null($this->arr['message'])) {
                    $mail->line($this->arr['name'].' attached this message with the invite:')
                         ->line('')
                         ->line('"'.nl2br($this->arr['message']).'"')
                         ->line('');
                }
                $mail->action('RSVP now', $this->arr['url'])
                     ->line($ignoreLine);

                return $mail;
            }
        } else { // receiver is someone not yet on the platform
            $mail = (new MailMessage())
                  ->subject($subject)
                  ->greeting($greeting)
                  ->line($introLine)
                  ->line('')
                  ->line($eventDetailsTable)
                  ->line('<br>')
                  ->line('You can turn up on the day, or if you would prefer you can also create an account with us and RSVP online now.');

            // Add message from sender if they left one
            if ( ! is_null($this->arr['message'])) {
                $mail->line('')
                     ->line($this->arr['name'].' attached this message with the invite:')
                     ->line('')
                     ->line('"'.nl2br($this->arr['message']).'"');
            }

            $mail->action('RSVP now', $this->arr['url'])
                 ->line($ignoreLine);

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

        ];
    }
}
