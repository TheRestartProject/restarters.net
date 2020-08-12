<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeleteEventFromWordpressFailed extends Notification implements ShouldQueue
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
        return (new MailMessage)
                      ->subject('Failed to delete event from WordPress: '.$this->arr['event_venue'])
                      ->greeting('Hello!')
                      ->line("Event deletion failed for {$this->arr['event_venue']} by {$this->arr['group_name']}.")
                      ->line(' ')
                      ->line('Please find and delete this event manually from WordPress.')
                      ->line(' ')
                      ->line('If you would like to stop receiving these notifications, please edit <a href="'.url('/user/edit/'.$notifiable->id).'">your preferences</a> on your account.');
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
            'title' => "Failed to delete event {$this->arr['event_venue']} by {$this->arr['group_name']} from WordPress",
        ];
    }
}
