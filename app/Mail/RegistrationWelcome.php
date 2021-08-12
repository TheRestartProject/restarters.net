<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegistrationWelcome extends Mailable
{
    use Queueable, SerializesModels;

    protected $firstName;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.registrationwelcome')
            ->subject('Welcome to the Restarters community')
            ->with([
                'firstName' => $this->firstName,
            ]);
    }
}
