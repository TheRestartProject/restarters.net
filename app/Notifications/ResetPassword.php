<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class ResetPassword extends BaseNotification
{
    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $locale = $notifiable->language;

        return (new MailMessage)
            ->subject(__('notifications.password_reset_subject', [], $locale))
            ->greeting(__('notifications.greeting', [], $locale))
            ->line(__('notifications.password_reset_line1', [], $locale))
            ->action(__('notifications.password_reset_action', [], $locale), $this->arr['url'] . "&locale=" . $locale)
            ->line(__('notifications.password_reset_noaction', [], $locale));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [

        ];
    }
}
