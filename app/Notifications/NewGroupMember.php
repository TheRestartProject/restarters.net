<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewGroupMember extends Notification
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
                  ->subject(__('notifications.new_member_subject', [
                      'name' => $this->arr['group_name']
                  ], $locale))
                  ->greeting(__('notifications.greeting', [], $locale))
                  ->line(__('notifications.new_member_line1', [
                        'user' => $this->arr['user_name'],
                        'group' => $this->arr['group_name']
                    ], $locale))
                  ->action(__('notifications.new_member_action', [], $locale), $this->arr['group_url'])
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
            'title' => __('notifications.new_member_title', [
                'name' => $this->arr['user_name']
                    ], $notifiable->language),
            'name' => $this->arr['group_name'],
            'url' => $this->arr['group_url'],
        ];
    }
}
