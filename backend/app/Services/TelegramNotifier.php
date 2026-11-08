<?php

namespace App\Services;

use App\Models\FinanceTransaction;
use App\Models\Order;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotifier
{
    protected ?string $botToken;
    protected ?string $chatId;

    public function __construct(?string $botToken = null, ?string $chatId = null)
    {
        $this->botToken = $botToken ?? config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN'));
        $this->chatId = $chatId ?? config('services.telegram.chat_id', env('TELEGRAM_CHAT_ID'));
    }

    /**
     * Send a raw HTML text message to the configured Telegram chat.
     * Skips silently if TELEGRAM_BOT_TOKEN or TELEGRAM_CHAT_ID is empty.
     */
    public function sendMessage(string $message): bool
    {
        if (empty($this->botToken) || empty($this->chatId)) {
            Log::debug('Telegram notification skipped: bot token or chat ID is empty.');
            return false;
        }

        try {
            $response = Http::timeout(5)
                ->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                    'chat_id' => $this->chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                ]);

            if ($response->failed()) {
                Log::warning('Telegram notification request failed with status ' . $response->status() . ': ' . $response->body());
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Telegram notification exception caught: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send new order notification to owner/admin.
     * Required info: kode pesanan, nama pelanggan, daftar item, total, metode ambil/kirim, tanggal ambil.
     */
    public function notifyNewOrder(Order $order): bool
    {
        $order->loadMissing(['items.product']);

        $kodePesanan = htmlspecialchars($order->kode_pesanan ?? '-', ENT_QUOTES, 'UTF-8');
        $namaPelanggan = htmlspecialchars($order->customer_name ?? '-', ENT_QUOTES, 'UTF-8');
        $telepon = htmlspecialchars($order->customer_phone ?? '-', ENT_QUOTES, 'UTF-8');
        $metode = $order->alamat ? htmlspecialchars($order->alamat, ENT_QUOTES, 'UTF-8') : 'Ambil di Toko';
        
        $tanggalAmbil = '-';
        if (!empty($order->tanggal_ambil)) {
            $tanggalAmbil = $order->tanggal_ambil instanceof \DateTimeInterface
                ? $order->tanggal_ambil->format('d/m/Y')
                : (string) $order->tanggal_ambil;
        }

        $totalFormatted = 'Rp ' . number_format((float) $order->total_price, 0, ',', '.');

        $daftarItem = '';
        foreach ($order->items as $item) {
            $namaProduk = htmlspecialchars($item->product?->nama ?? 'Produk', ENT_QUOTES, 'UTF-8');
            $subtotalFormatted = 'Rp ' . number_format((float) $item->subtotal, 0, ',', '.');
            $daftarItem .= "• {$namaProduk} x {$item->qty} ({$subtotalFormatted})\n";
        }

        $catatan = '';
        if (!empty($order->catatan)) {
            $catatan = "\n<b>Catatan:</b> " . htmlspecialchars($order->catatan, ENT_QUOTES, 'UTF-8');
        }

        $message = "🍞 <b>PESANAN BARU MASUK!</b>\n\n"
            . "<b>Kode Pesanan:</b> <code>{$kodePesanan}</code>\n"
            . "<b>Pelanggan:</b> {$namaPelanggan} ({$telepon})\n"
            . "<b>Metode / Alamat:</b> {$metode}\n"
            . "<b>Tanggal Ambil / Kirim:</b> {$tanggalAmbil}\n\n"
            . "<b>Daftar Item:</b>\n{$daftarItem}\n"
            . "<b>Total:</b> <b>{$totalFormatted}</b>"
            . $catatan;

        return $this->sendMessage($message);
    }

    /**
     * Send payment received notification to owner/admin.
     * Required info: kode pesanan, nominal, status bayar.
     */
    public function notifyPaymentReceived(Payment $payment): bool
    {
        $payment->loadMissing(['order']);
        $order = $payment->order;

        $kodePesanan = htmlspecialchars($order?->kode_pesanan ?? '-', ENT_QUOTES, 'UTF-8');
        $nominalFormatted = 'Rp ' . number_format((float) $payment->nominal, 0, ',', '.');
        $statusBayar = htmlspecialchars(ucfirst($order?->payment_status ?? 'unpaid'), ENT_QUOTES, 'UTF-8');
        $metode = htmlspecialchars(strtoupper($payment->metode ?? 'CASH'), ENT_QUOTES, 'UTF-8');
        $tipe = htmlspecialchars(strtoupper($payment->tipe ?? 'PEMBAYARAN'), ENT_QUOTES, 'UTF-8');

        $tanggal = '-';
        if (!empty($payment->tanggal)) {
            $tanggal = $payment->tanggal instanceof \DateTimeInterface
                ? $payment->tanggal->format('d/m/Y')
                : (string) $payment->tanggal;
        }

        $message = "💰 <b>PEMBAYARAN PESANAN DITERIMA!</b>\n\n"
            . "<b>Kode Pesanan:</b> <code>{$kodePesanan}</code>\n"
            . "<b>Nominal:</b> <b>{$nominalFormatted}</b>\n"
            . "<b>Status Bayar Pesanan:</b> {$statusBayar}\n"
            . "<b>Metode:</b> {$metode} ({$tipe})\n"
            . "<b>Tanggal:</b> {$tanggal}";

        return $this->sendMessage($message);
    }

    /**
     * Send daily financial summary to owner/admin.
     */
    public function notifyDailyFinanceSummary(?string $date = null): bool
    {
        $targetDate = $date ? Carbon::parse($date) : Carbon::today();
        $dateStr = $targetDate->format('Y-m-d');
        $displayDate = $targetDate->format('d/m/Y');

        $transactions = FinanceTransaction::whereDate('tanggal', $dateStr)->get();

        $pemasukan = (float) $transactions->where('tipe', 'pemasukan')->sum('nominal');
        $pengeluaran = (float) $transactions->where('tipe', 'pengeluaran')->sum('nominal');
        $labaBersih = $pemasukan - $pengeluaran;

        $ordersToday = Order::whereDate('created_at', $dateStr)->get();
        $totalOrders = $ordersToday->count();
        $paidOrders = $ordersToday->where('payment_status', 'paid')->count();
        $partialOrders = $ordersToday->where('payment_status', 'partial')->count();
        $unpaidOrders = $ordersToday->where('payment_status', 'unpaid')->count();

        $pemasukanFmt = 'Rp ' . number_format($pemasukan, 0, ',', '.');
        $pengeluaranFmt = 'Rp ' . number_format($pengeluaran, 0, ',', '.');
        $labaFmt = 'Rp ' . number_format($labaBersih, 0, ',', '.');
        $labaEmoji = $labaBersih >= 0 ? '🟢' : '🔴';

        $message = "📊 <b>REKAP KEUANGAN HARIAN ERLES BAKERY</b>\n"
            . "📅 <b>Tanggal:</b> {$displayDate}\n"
            . "━━━━━━━━━━━━━━━━━━━━\n"
            . "💰 <b>Pemasukan:</b> {$pemasukanFmt}\n"
            . "💸 <b>Pengeluaran:</b> {$pengeluaranFmt}\n"
            . "{$labaEmoji} <b>Laba/Rugi Bersih:</b> <b>{$labaFmt}</b>\n"
            . "━━━━━━━━━━━━━━━━━━━━\n"
            . "📦 <b>Aktivitas Pesanan:</b>\n"
            . "• Total Pesanan: <b>{$totalOrders}</b>\n"
            . "  - Lunas: {$paidOrders}\n"
            . "  - DP / Sebagian: {$partialOrders}\n"
            . "  - Belum Bayar: {$unpaidOrders}\n\n"
            . "<i>Laporan otomatis sistem Erles Bakery ERP</i>";

        return $this->sendMessage($message);
    }
}

