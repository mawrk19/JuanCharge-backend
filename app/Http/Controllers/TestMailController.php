<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeLguUserMail;
use App\Models\LguUser;
use Illuminate\Support\Facades\URL;

class TestMailController extends Controller
{
    public function sendTestLguWelcomeEmail()
    {
        $user = new LguUser([
            'email' => 'gercee19@gmail.com',
            'first_name' => 'Gercee',
            'last_name' => 'Acedo',
        ]);
        $user->id = 1; // Mock user id

        $verificationUrl = URL::temporarySignedRoute(
            'lgu.email.verify',
            now()->addHour(),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        try {
            Mail::to($user->email)->send(new WelcomeLguUserMail($user, $verificationUrl));
            return "Test LGU welcome email sent to gercee19@gmail.com";
        } catch (\Exception $e) {
            return "Error sending email: " . $e->getMessage();
        }
    }
}
