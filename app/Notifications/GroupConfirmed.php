<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class GroupConfirmed extends BaseNotification
{
    protected $group;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($group)
    {
        $this->group = $group;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $locale = $notifiable->language;

        return (new MailMessage)
            ->subject(__('notifications.group_confirmed_subject', [], $locale))
            ->greeting(__('notifications.greeting', [], $notifiable->language))
            ->line(
                __('notifications.group_confirmed_line1', [
                    'name' => $this->group->name
                ], $locale)
            )
            ->action(__('notifications.view_group', [], $locale), url('/group/view/' . $this->group->idgroups))
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
     */
    public function toArray($notifiable): array
    {
        return [
            'name' => $this->group->name,
            'url' => url('/group/view/' . $this->group->idgroups)
        ];
    }
}
