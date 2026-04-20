<?php

namespace App\Services;

use Twilio\Rest\Client;

class TwilioService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client(
            env('TWILIO_SID'),
            env('TWILIO_AUTH_TOKEN')
        );
    }

    public function sendSms(string $to, string $message): array
    {
        try {
            $messageInstance = $this->client->messages->create($to, [
                'from' => env('TWILIO_PHONE_NUMBER'),
                'body' => $message,
            ]);

            return ['success' => true, 'sid' => $messageInstance->sid];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
