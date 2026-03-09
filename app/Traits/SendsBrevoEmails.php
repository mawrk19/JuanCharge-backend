<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait SendsBrevoEmails
{
    /**
     * Send OTP via Brevo Email API
     * 
     * @param string $email
     * @param string $otp
     */
    protected function sendBrevoOtpEmail($email, $otp)
    {
        $subject = 'Your Login Verification Code';
        $htmlContent = "
            <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
                <h2>Verification Code</h2>
                <p>Your OTP code is:</p>
                <h1 style='color: #4CAF50; font-size: 32px; letter-spacing: 5px;'>{$otp}</h1>
                <p>This code will expire in 10 minutes.</p>
            </div>
        ";

        return $this->sendEmailViaBrevo($email, $subject, $htmlContent);
    }

    /**
     * Send email via Brevo API
     * 
     * @param string $to
     * @param string $subject
     * @param string $htmlContent
     * @return bool
     */
    protected function sendEmailViaBrevo($to, $subject, $htmlContent)
    {
        $apiKey = config('services.brevo.key');

        if (!$apiKey) {
            Log::error('Brevo API key not configured');
            return false;
        }

        $response = Http::withOptions(['verify' => false])->withHeaders([
            'api-key' => $apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])->post('https://api.brevo.com/v3/smtp/email', [
                    'sender' => [
                        'name' => config('app.name', 'JuanCharge'),
                        'email' => config('mail.from.address', 'no-reply@juancharge.com')
                    ],
                    'to' => [
                        ['email' => $to]
                    ],
                    'subject' => $subject,
                    'htmlContent' => $htmlContent
                ]);

        if (!$response->successful()) {
            Log::error('Brevo Email Error: ' . $response->body());
            return false;
        }

        return true;
    }

    /**
     * Send SMS via Brevo API
     * 
     * @param string $mobile
     * @param string $content
     * @return bool
     */
    protected function sendBrevoSms($mobile, $content)
    {
        $apiKey = config('services.brevo.key');

        if (!$apiKey) {
            Log::error('Brevo API key not configured');
            return false;
        }

        $response = Http::withOptions(['verify' => false])->withHeaders([
            'api-key' => $apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])->post('https://api.brevo.com/v3/transactionalSMS/sms', [
                    'sender' => 'JuanCharge',
                    'recipient' => $mobile,
                    'content' => $content
                ]);

        if (!$response->successful()) {
            Log::error('Brevo SMS Error: ' . $response->body());
            return false;
        }

        return true;
    }
}
