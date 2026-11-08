<?php

namespace App\Console\Commands;

use App\Services\TelegramNotifier;
use Illuminate\Console\Command;

class SendTelegramFinanceRecapCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:rekap-keuangan {--date= : Tanggal rekap keuangan (format YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim ringkasan rekap keuangan ke Telegram owner/admin';

    /**
     * Execute the console command.
     */
    public function handle(TelegramNotifier $notifier): int
    {
        $date = $this->option('date');
        $this->info("Mengirim rekap keuangan Telegram untuk tanggal " . ($date ?? 'hari ini') . "...");

        $success = $notifier->notifyDailyFinanceSummary($date);

        if ($success) {
            $this->info("Rekap keuangan berhasil dikirim ke Telegram!");
            return Command::SUCCESS;
        }

        $this->warn("Gagal mengirim rekap atau notifikasi Telegram dilewati (token/chat_id belum dikonfigurasi).");
        return Command::FAILURE;
    }
}

