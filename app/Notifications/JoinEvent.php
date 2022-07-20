<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class JoinEvent extends BaseNotification
{
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

        // If we're sending to a registered user, only send by mail channel if opted in to emails.
        if ($this->user !== null) {
            $channels[] = 'database';

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
        $locale = $notifiable->language;
        $subject = __('notifications.join_event_subject', [
            'groupname' => $this->arr['group']
        ], $locale);
        $greeting = __('notifications.greeting', [], $locale);

        $introLine = __('notifications.join_event_line1', [
            'inviter' => $this->arr['name'],
            'groupname' => $this->arr['group']
        ], $locale);

        $eventDetailsTable = '<table style="margin-left:120px; color:black">
                                    <tr>
                                    <td>' . __('notifications.join_event_date') . '</td>
                                    <td>'.$this->arr['event']->getFormattedLocalStart('D jS M Y').'</td>
                                  </tr>
                                  <tr>
                                    <td>' . __('notifications.join_event_time') . '</td>
                                    <td>'.$this->arr['event']->getEventStartEndLocal(true).'</td>
                                  </tr>
                                  <tr>
                                    <td>' . __('notifications.join_event_location') . '</td>
                                    <td>'.$this->arr['event']->location.'</td>
                                  </tr>
                                  <tr>
                                    <td colspan="2"><a href="' . $this->arr['view_url'] . '">' . __('notifications.join_event_view') . '</a></td>
                                  </tr>
                            </table>';
        $ignoreLine = __('notifications.join_event_ignore', [], $locale);

        if ($this->user !== null) { // existing user
            if ($this->user->invites == 1) { // opted in to emails
                $mail = (new MailMessage)
                      ->subject($subject)
                      ->greeting($greeting)
                      ->line($introLine)
                      ->line('');

                // Add message from sender if they left one
                if (! is_null($this->arr['message'])) {
                    $mail->line('"'.nl2br($this->arr['message']).'"')
                         ->line('');
                }

                $mail->line($eventDetailsTable)
                     ->line($ignoreLine)
                     ->line('');

                return $mail;
            }
        } else { // receiver is someone not yet on the platform
            $mail = (new MailMessage())
                  ->subject($subject)
                  ->greeting($greeting)
                  ->line($introLine)
                  ->line('');

            // Add message from sender if they left one
            if (! is_null($this->arr['message'])) {
                $mail->line('')
                     ->line('"'.nl2br($this->arr['message']).'"');
            }

            $mail->line($eventDetailsTable)
                 ->line(__('notifications.join_event_rsvp_now', [], $locale))
                 ->line('')
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
            'title' => __('notifications.join_event_title', [], $notifiable->locale),
            'name' => $this->arr['event']->venue,
            'url' => url('/party/view/'.$this->arr['event']->idevents),
        ];
    }
}
