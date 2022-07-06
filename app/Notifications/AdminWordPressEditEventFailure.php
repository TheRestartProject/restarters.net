<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class AdminWordPressEditEventFailure extends BaseNotification
{
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
        if ($notifiable->invites == 1) {
            return ['mail', 'database'];
        }

        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                  ->subject('Event WordPress failure')
                  ->greeting(__('notifications.greeting', [], $notifiable->language))
                  ->line('Event \''.$this->arr['event_venue'].'\' failed to post to WordPress during an edit to the event.')
                  ->action('View event', $this->arr['event_url'])
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
        return [
            'title' => 'Event failed to save to an existing WordPress post:',
            'name' => $this->arr['event_venue'],
            'url' => $this->arr['event_url'],
        ];
    }
}
