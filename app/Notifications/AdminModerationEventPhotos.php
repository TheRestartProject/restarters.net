<?php

namespace App\Notifications;

use App\Party;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminModerationEventPhotos extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var Party
     */
    protected $party;

    protected $arr;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($party, $arr)
    {
        $this->party = $party;
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
                      ->subject('New event photos uploaded to event: '.$this->arr['event_venue'])
                      ->greeting('Hello!')
                      ->line('Photos have been uploaded to an event, <b>'.$this->arr['event_venue'].'</b>, held by <b>'
                          . $this->party->group_name . '</b> on ' . $this->party->getEventDateVerbose() . ' at ' . $this->party->getEventStartEnd())
                      ->action('View event', $this->arr['event_url'])
                      ->line($this->listImageThumbnails() . '<br /><br />')
                      ->line('These photos might need your moderation, if they haven\'t yet been moderated by another administrator.')
                      ->line('If you would like to stop receiving these notifications, please edit <a href="'.url('/user/edit/'.$notifiable->id).'">your preferences</a> on your account.');
    }

    protected function listImageThumbnails()
    {
        $images = [end($this->party->images)]; // Trying to make it easy to list multiple images later if we want to
        $html = '';

        foreach ($images as $image) {
            $html .= '<a href="' . $this->arr['event_url'] . '#event-photo-' . $image->idimages . '"><img src="' . url('/uploads/thumbnail_' . $image->path) . '" width="100"></a>';
        }

        return $html;
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
            'title' => 'New event photos uploaded:',
            'event_id' => $this->arr['event_id'],
            'name' => $this->arr['event_venue'],
            'url' => $this->arr['event_url'],
        ];
    }
}
