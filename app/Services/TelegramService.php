<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pengirim pesan Telegram (BL-10).
 * Token bot disimpan di .env (TELEGRAM_BOT_TOKEN) dan tidak pernah dicatat di log.
 */
class TelegramService
{
    public function configured(): bool
    {
        return filled(config('telegram.bot_token'));
    }

    public function sendMessage(string $chatId, string $text): bool
    {
        if (! $this->configured()) {
            Log::warning('Telegram bot belum dikonfigurasi (TELEGRAM_BOT_TOKEN kosong).');

            return false;
        }

        try {
            $response = Http::timeout(15)
                ->asJson()
                ->post($this->apiUrl('sendMessage'), [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Gagal terhubung ke Telegram API.');

            return false;
        }

        return $response->successful();
    }

    /**
     * URL endpoint API Telegram untuk sebuah method.
     * Format wajib: https://api.telegram.org/bot<TOKEN>/<method>
     * — awalan "bot" menempel langsung sebelum token, tanpa spasi.
     */
    private function apiUrl(string $method): string
    {
        return 'https://api.telegram.org/bot'.config('telegram.bot_token').'/'.$method;
    }
}
