<?php

namespace App\Mail;

use Illuminate\Mail\Transport\Transport;
use Illuminate\Support\Facades\Http;
use Swift_Mime_SimpleMessage;

class BrevoApiTransport extends Transport
{
    protected $apiKey;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $payload = [
            'sender' => [
                'email' => $message->getFrom() ? array_keys($message->getFrom())[0] : config('mail.from.address'),
                'name' => $message->getFrom() ? array_values($message->getFrom())[0] : config('mail.from.name'),
            ],
            'to' => collect($message->getTo())->map(function ($name, $email) {
                return ['email' => $email, 'name' => $name];
            })->values()->toArray(),
            'subject' => $message->getSubject(),
            'htmlContent' => $message->getBody(),
        ];

        try {
            $response = Http::withHeaders([
                'api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.brevo.com/v3/smtp/email', $payload);

            if ($response->successful()) {
                $this->sendPerformed($message);
                return $this->numberOfRecipients($message);
            }

            throw new \Exception('Brevo API Error: ' . $response->body());
        } catch (\Exception $e) {
            throw new \Exception('Failed to send email via Brevo API: ' . $e->getMessage());
        }
    }
}