<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JoinGroup extends Notification implements ShouldQueue
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
        $subject = __('notifications.join_group_title', [
            'name' => $this->arr['name'],
            'group' => $this->arr['group']
        ]);
        $introLine = __('notifications.join_group_intro', [
            'name' => $this->arr['name'],
            'group' => $this->arr['group']
        ]);
        $actionText = __('notifications.join_group_action');
        $ignoreLine = __('notifications.join_group_ignore');

        if (! is_null($this->user)) { // user is already on the platform
            if ($this->user->invites == 1) { // user has opted in to receive emails
                $mail = (new MailMessage)
                      ->subject($subject)
                      ->greeting(__('notifications.greeting', [], $notifiable->language))
                      ->line($introLine)
                      ->line('');

                if (! is_null($this->arr['message'])) { // host has added a message
                    $mail->line(__('notifications.join_group_attached', [
                        'name' => $this->arr['name']
                    ]))
                         ->line('')
                         ->line('"'.$this->arr['message'].'"')
                         ->line('');
                }

                $mail->action($actionText, $this->arr['url']);

                $mail->line('');
                $mail->line($ignoreLine);

                return $mail;
            }
        } else { // users not yet on the platform
            $mail = (new MailMessage)
                    ->subject($subject)
                    ->greeting(__('notifications.greeting', [], $notifiable->language))
                    ->line($introLine)
                    ->line('');

            if (! is_null($this->arr['message'])) { // host has added a message
                $mail->line(__('notifications.join_group_attached', [
                    'name' => $this->arr['name']
                ]))
                     ->line('')
                     ->line('"'.$this->arr['message'].'"')
                     ->line('');
            }

            $mail->action($actionText, $this->arr['url'])
                  ->line(__('notifications.join_group_more', [
                      'more' => env('APP_URL').'/about'
            ]));

            $mail->line('');
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

        ];
    }
}
