<?php

namespace App\Notifications;

use App\Models\Party;
use Illuminate\Notifications\Messages\MailMessage;

class EventConfirmed extends BaseNotification
{
    public $party;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Party $party)
    {
        $this->party = $party;
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
        $url = url('/party/view/' . $this->party->idevents);

        return (new MailMessage)
            ->subject(__('notifications.event_confirmed_subject', [
                'time' => $this->party->event_date_local . ' ' . $this->party->start_local
            ], $locale))
            ->greeting(__('notifications.greeting', [], $locale))
            ->line(
                __('notifications.event_confirmed_line1', [
                    'name' => $this->party->venue,
                    'url' => $url
                ], $locale)
            )
            ->action(__('notifications.view_event', [], $locale), url('/'))
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
        $locale = $notifiable->language;
        $url = url('/party/view/' . $this->party->idevents);

        return [
            'title' => __('notifications.event_confirmed_title', [], $locale),
            'event_id' => $this->party->idevents,
            'name' => $this->party->venut,
            'url' => $url,
        ];
    }
}
