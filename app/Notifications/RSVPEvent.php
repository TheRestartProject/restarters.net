<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class RSVPEvent extends BaseNotification
{
    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $locale = $notifiable->language;

        return (new MailMessage)
          ->subject(__('notifications.rsvp_subject', [
              'name' => $this->arr['user_name']
          ], $locale))
          ->greeting(__('notifications.greeting', [], $locale))
          ->line(__('notifications.rsvp_line1', [
              'user' => $this->arr['user_name'],
              'event' => $this->arr['event_venue']
          ], $locale))
          ->action(__('notifications.rsvp_action', [], $locale), $this->arr['event_url'])
          ->line(__('notifications.email_preferences', [
              'url' => url('/user/edit/' . $notifiable->id)
          ]));
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
            'title' => __('notifications.rsvp_title', [
                'name' => $this->arr['user_name']
            ], $notifiable->language),
            'name' => $this->arr['event_venue'],
            'url' => $this->arr['event_url'],
        ];
    }
}
