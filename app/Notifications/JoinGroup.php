<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class JoinGroup extends BaseNotification
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
        $locale = $notifiable->language;
        $subject = __('notifications.join_group_title', [
            'name' => $this->arr['name'],
            'group' => $this->arr['group']
        ], $locale);
        $introLine = __('notifications.join_group_intro', [
            'name' => $this->arr['name'],
            'group' => $this->arr['group']
        ], $locale);
        $actionText = __('notifications.join_group_action', [], $locale);
        $ignoreLine = __('notifications.join_group_ignore', [], $locale);

        if (! is_null($this->user)) { // user is already on the platform
            if ($this->user->invites == 1) { // user has opted in to receive emails
                $mail = (new MailMessage)
                      ->subject($subject)
                      ->greeting(__('notifications.greeting', [], $locale))
                      ->line($introLine)
                      ->line('');

                if (! is_null($this->arr['message'])) { // host has added a message
                    $mail->line(__('notifications.join_group_attached', [
                        'name' => $this->arr['name']
                    ], $locale))
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
                    ->greeting(__('notifications.greeting', [], $locale))
                    ->line($introLine)
                    ->line('');

            if (! is_null($this->arr['message'])) { // host has added a message
                $mail->line(__('notifications.join_group_attached', [
                    'name' => $this->arr['name']
                ], $locale))
                     ->line('')
                     ->line('"'.$this->arr['message'].'"')
                     ->line('');
            }

            $mail->action($actionText, $this->arr['url'])
                  ->line(__('notifications.join_group_more', [
                      'more' => env('APP_URL').'/about'
            ], $locale));

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
