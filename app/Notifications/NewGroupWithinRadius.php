<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class NewGroupWithinRadius extends BaseNotification
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
        $locale = $notifiable->language;
        return (new MailMessage)
                    ->subject(__('notifications.new_group_subject', [], $locale))
                    ->greeting(__('notifications.greeting', [], $notifiable->language))
                    ->line(__('notifications.new_group_line1', [
                        'name' => $this->arr['group_name']
                    ], $locale))
                    ->action(__('notifications.new_group_action', [
                        'name' => $this->arr['group_name']
                    ], $locale), $this->arr['group_url'])
                    ->line(__('notifications.email_preferences', [
                       'url' => url('/user/edit/'.$notifiable->id)
                    ], $locale));
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
            'title' => __('notifications.new_group_title', [], $notifiable->language),
            'name' => $this->arr['group_name'],
            'url' => $this->arr['group_url'],
        ];
    }
}
