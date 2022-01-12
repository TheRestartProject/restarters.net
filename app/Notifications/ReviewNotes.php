<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewNotes extends Notification implements ShouldQueue
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
        if ($notifiable == null) {
            return [];
        }

        $channels = [];
        if ($notifiable->invites) {
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
        if ($notifiable !== null && $notifiable->invites == 1) {
            $locale = $notifiable->language;

            return (new MailMessage)
                  ->subject('A repair has been marked as suitable for the wiki')
                  ->greeting(__('notifications.greeting', [], $notifiable->language))
                  ->line($this->arr['current_user_name'].' has marked a repair as suitable for the wiki.')
                  ->action('View device', $this->arr['device_url'])
                ->line(__('notifications.email_preferences', [
                    'url' => url('/user/edit/'.$notifiable->id)
                ], $locale));
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
