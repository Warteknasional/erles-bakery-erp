<?php

namespace App\Console\Commands;

use App\Services\TelegramBotHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramPollCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:poll {--once : Jalankan sekali lalu keluar} {--timeout=20 : Durasi timeout long-polling dalam detik}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Jalankan bot Telegram interaktif mode long-polling untuk menerima pesan & perintah owner';

    /**
     * Execute the console command.
     */
    public function handle(TelegramBotHandler $handler): int
    {
        $botToken = config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN'));

        if (empty($botToken)) {
            $this->error('TELEGRAM_BOT_TOKEN belum diset di file .env');
            return Command::FAILURE;
        }

        $isOnce = $this->option('once');
        $pollTimeout = (int) $this->option('timeout');
        $offset = 0;

        $this->info("🤖 Bot Telegram Erles Bakery aktif (mode " . ($isOnce ? "once" : "long-polling") . ")...");
        $this->info("Menunggu perintah dari Telegram...");

        do {
            try {
                $response = Http::timeout($pollTimeout + 5)->get("https://api.telegram.org/bot{$botToken}/getUpdates", [
                    'offset' => $offset,
                    'timeout' => $pollTimeout,
                ]);

                if ($response->successful()) {
                    $updates = $response->json('result', []);

                    foreach ($updates as $update) {
                        $updateId = $update['update_id'];
                        $offset = $updateId + 1;

                        $sender = $update['message']['from']['first_name'] ?? 'Unknown';
                        $text = $update['message']['text'] ?? '[non-text]';

                        $this->line("📩 Pesan diterima dari [{$sender}]: {$text}");

                        $handler->handleUpdate($update);
                    }
                } else {
                    $this->warn("Telegram getUpdates mengembalikan status: " . $response->status());
                    sleep(2);
                }
            } catch (\Throwable $e) {
                // Ignore transient curl/network timeouts during long polling
                if (!str_contains($e->getMessage(), 'timed out') && !str_contains($e->getMessage(), 'cURL error 28')) {
                    Log::warning('Telegram polling exception: ' . $e->getMessage());
                    $this->warn("Koneksi polling terganggu: " . $e->getMessage());
                }
                sleep(2);
            }

            if ($isOnce) {
                break;
            }

            usleep(500000); // 0.5s pause
        } while (true);

        return Command::SUCCESS;
    }
}

