<?php

namespace App\Services;

use App\Jobs\SendWahaMessageJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaClient
{
    protected ?string $baseUrl;
    protected string $session;
    protected ?string $apiKey;

    public function __construct(
        ?string $baseUrl = null,
        ?string $session = null,
        ?string $apiKey = null
    ) {
        $rawUrl = $baseUrl ?? config('services.waha.url', env('WAHA_URL'));
        $this->baseUrl = $rawUrl ? rtrim($rawUrl, '/') : null;
        $this->session = $session ?? config('services.waha.session', env('WAHA_SESSION', 'default')) ?: 'default';
        $this->apiKey = $apiKey ?? config('services.waha.api_key', env('WAHA_API_KEY'));
    }

    /**
     * Send text message directly to a WhatsApp chat via WAHA.
     * Skips silently without throwing error if WAHA_URL is empty.
     */
    public function sendText(string $chatId, string $text): bool
    {
        if (empty($this->baseUrl)) {
            Log::debug('WAHA client skipped: WAHA_URL is empty.');
            return false;
        }

        $formattedChatId = $this->formatChatId($chatId);

        try {
            $client = Http::timeout(5);

            if (!empty($this->apiKey)) {
                $client = $client->withHeaders([
                    'X-Api-Key' => $this->apiKey,
                ]);
            }

            $response = $client->post("{$this->baseUrl}/api/sendText", [
                'session' => $this->session,
                'chatId' => $formattedChatId,
                'text' => $text,
            ]);

            if ($response->failed()) {
                Log::warning("WAHA sendText failed with status {$response->status()}: " . $response->body());
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('WAHA sendText exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Queue a text message to be sent after the active DB transaction commits.
     */
    public function queueText(string $chatId, string $text): void
    {
        dispatch(new SendWahaMessageJob($chatId, $text))->afterCommit();
    }

    /**
     * Format phone number or chatId into WAHA standard format (e.g., 62812345678@c.us).
     */
    public function formatChatId(string $chatId): string
    {
        $chatId = trim($chatId);

        if (str_contains($chatId, '@')) {
            return $chatId;
        }

        // Clean non-numeric characters
        $digits = preg_replace('/\D/', '', $chatId);

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '628' . substr($digits, 1);
        }

        return $digits . '@c.us';
    }
}

