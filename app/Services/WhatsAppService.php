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

    public function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }

        if (! str_starts_with($digits, '62')) {
            return null;
        }

        if (! preg_match('/^62[0-9]{8,15}$/', $digits)) {
            return null;
        }

        return $digits;
    }

    public function isValidPhone(string $phone): bool
    {
        return $this->normalizePhone($phone) !== null;
    }

    public function sendMessage(string $phone, string $message): bool
    {
        try {
            $normalizedPhone = $this->normalizePhone($phone);

            if (! $normalizedPhone) {
                Log::warning('WhatsApp message skipped: invalid phone number', [
                    'phone' => $phone,
                ]);

                return false;
            }

            $data = [
                'phone' => $normalizedPhone,
                'message' => $message,
            ];

            Log::info('Attempting to send WhatsApp message', [
                'phone' => $normalizedPhone,
                'message_length' => strlen($message),
            ]);

            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->post($this->baseUrl, $data);

            if ($response->successful()) {
                Log::info('WhatsApp message sent successfully', [
                    'phone' => $normalizedPhone,
                    'response' => $response->json(),
                ]);
                return true;
            } else {
                Log::error('Failed to send WhatsApp message', [
                    'phone' => $normalizedPhone,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception sending WhatsApp message: ' . $e->getMessage(), [
                'phone' => $phone,
            ]);
            return false;
        }
    }
}
