<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventRepairs extends Notification implements ShouldQueue
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
            return ['database', 'mail'];
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
                    ->subject('Help us log repair info for '.$this->arr['event_name'])
                    ->greeting('Hello!')
                    ->line('Thank you for fixing at the \''.$this->arr['event_name'].'\' event. The host has posted photos of any feedback left by participants and repair data. Please help us to improve the details of the repairs you carried out by adding any useful information or photos you have. Any extra details you can add will help future repair attempts.')
                    ->action('Contribute repair info', url($this->arr['event_url']))
                    ->line('If you would like to stop receiving these emails, please visit <a href="'.$this->arr['preferences'].'">your preferences</a> on your account.');
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
            'title' => 'Help us log repair info for ',
            'name' => $this->arr['event_name'],
            'url' => $this->arr['event_url'],
        ];
    }
}
