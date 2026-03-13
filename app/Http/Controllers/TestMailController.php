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
        // Actually fetch the real first user from DB to satisfy the SerializesModels trait 
        // on the Mailable class to stop the ModelNotFoundException.
        $user = LguUser::first();

        // If no user exists, let's complain, otherwise we fake an email address temporarily.
        if (!$user) {
            return "Error: No LguUser exists in the database. Please create one first before testing.";
        }
        
        // Temporarily override the email for local testing
        $user->email = 'gercee19@gmail.com';
        $user->first_name = 'Gercee';

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
