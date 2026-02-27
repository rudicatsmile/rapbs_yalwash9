<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = config('services.whatsapp.base_url', 'https://jkt.wablas.com/api/send-message');
        $this->token = config('services.whatsapp.token', 'WVt3FebWwGOIO3peZHApBlNOGyjuBgb9HJ3ntPDxI136ZWIbM6pTVlRQsDOOXBHs.EN5eKMMv');
    }

    public function sendMessage(string $phone, string $message): bool
    {
        try {
            $data = [
                'phone' => $phone,
                'message' => $message,
            ];

            Log::info('Attempting to send WhatsApp message', [
                'phone' => $phone,
                'message_length' => strlen($message)
            ]);

            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->post($this->baseUrl, $data);

            if ($response->successful()) {
                Log::info('WhatsApp message sent successfully', [
                    'phone' => $phone,
                    'response' => $response->json()
                ]);
                return true;
            } else {
                Log::error('Failed to send WhatsApp message', [
                    'phone' => $phone,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception sending WhatsApp message: ' . $e->getMessage(), [
                'phone' => $phone
            ]);
            return false;
        }
    }
}
