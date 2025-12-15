<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeRegisteredKioskUser extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $password;

    /**
     * Create a new message instance.
     *
     * @param $user
     * @param $password
     */
    public function __construct($user, $password)
    {
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.welcome-registered-kiosk-user')
                    ->subject('Welcome to JuanCharge! Your Account is Ready')
                    ->with([
                        'userName' => $this->user->name,
                        'email' => $this->user->email,
                        'password' => $this->password,
                        'points' => $this->user->points_balance ?? 0,
                    ]);
    }
}
