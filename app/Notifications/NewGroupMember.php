<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class NewGroupMember extends BaseNotification
{
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
            ->subject(
                __('notifications.new_member_subject', [
                    'name' => $this->arr['group_name']
                ], $locale)
            )
            ->greeting(__('notifications.greeting', [], $locale))
            ->line(
                __('notifications.new_member_line1', [
                    'user' => $this->arr['user_name'],
                    'group' => $this->arr['group_name']
                ], $locale)
            )
            ->action(__('notifications.new_member_action', [], $locale), $this->arr['group_url'])
            ->line(
                __('notifications.email_preferences', [
                    'url' => url('/user/edit/' . $notifiable->id)
                ], $locale)
            );
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
            'title' => __('notifications.new_member_title', [
                'name' => $this->arr['user_name']
            ],            $notifiable->language),
            'name' => $this->arr['group_name'],
            'url' => $this->arr['group_url'],
        ];
    }
}
