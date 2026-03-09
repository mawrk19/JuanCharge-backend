<?php

namespace App\Mail;

use App\Models\LguUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeLguUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public LguUser $user;
    public string $verificationUrl;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(LguUser $user, string $verificationUrl)
    {
        $this->user = $user;
        $this->verificationUrl = $verificationUrl;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Welcome to JuanCharge - Verify Your Email')
                    ->markdown('emails.welcome-lgu-user');
    }
}
