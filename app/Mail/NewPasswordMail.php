<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $newPassword;
    public $userName;
    public $email;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($newPassword, $email, $userName = null)
    {
        $this->newPassword = $newPassword;
        $this->email = $email;
        $this->userName = $userName;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Your New Password - JuanCharge')
                    ->view('emails.new-password');
    }
}
