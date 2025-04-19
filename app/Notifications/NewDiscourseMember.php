<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class NewDiscourseMember extends BaseNotification
{
    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(
                __('groups.talk_group_add_title', [
                    'group_name' => $this->arr['group_name']
                ])
            )
            ->greeting(__('partials.notification_greeting'))
            ->line(
                __('groups.talk_group_add_body', [
                    'group_name' => $this->arr['group_name']
                ])
            )
            ->line(
                __('notifications.email_preferences', [
                    'url' => url('/user/edit/' . $notifiable->id)
                ])
            );
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     */
    public function toArray($notifiable): array
    {
        return [
            'title' => __('groups.talk_group_add_title', [
                'group_name' => $this->arr['group_name']
            ]),
            'group_name' => $this->arr['group_name']
        ];
    }
}
