<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeKioskUser extends Mailable
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
        return $this->view('emails.welcome-kiosk-user')
                    ->subject('Welcome to JuanCharge - Your Kiosk Account')
                    ->with([
                        'userName' => $this->user->name,
                        'email' => $this->user->email,
                        'password' => $this->password,
                        'points' => $this->user->points_balance ?? 0,
                    ]);
    }
}
