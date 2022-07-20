<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class AdminModerationEventPhotos extends BaseNotification
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
                      ->subject(__('notifications.new_event_photos_subject', [
                          'event' => $this->arr['event_venue']
                      ], $locale))
                      ->greeting(__('notifications.greeting', [], $locale))
                      ->line('Photos have been uploaded to an event: \''.$this->arr['event_venue'].'\'.')
                      ->action('View event', $this->arr['event_url'])
                      ->line('These photos might need your moderation, if they haven\'t yet been moderated by another administrator.')
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
        $locale = $notifiable->language;
        return [
            'title' => __('notifications.new_event_photos_subject', [], $locale),
            'event_id' => $this->arr['event_id'],
            'name' => $this->arr['event_venue'],
            'url' => $this->arr['event_url'],
        ];
    }
}
