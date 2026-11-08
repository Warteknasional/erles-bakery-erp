<?php

namespace App\Services;

use App\Models\FinanceTransaction;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotHandler
{
    protected ?string $botToken;
    protected ?string $ownerChatId;

    public function __construct(?string $botToken = null, ?string $ownerChatId = null)
    {
        $this->botToken = $botToken ?? config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN'));
        $this->ownerChatId = $ownerChatId ?? config('services.telegram.chat_id', env('TELEGRAM_CHAT_ID'));
    }

    /**
     * Send HTML message to a chat ID.
     */
    public function sendMessage(string $chatId, string $text): bool
    {
        if (empty($this->botToken)) {
            Log::warning('TelegramBotHandler: botToken is not configured.');
            return false;
        }

        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('TelegramBotHandler sendMessage error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Handle incoming Telegram update.
     */
    public function handleUpdate(array $update): void
    {
        $message = $update['message'] ?? null;
        if (!$message || !isset($message['text'])) {
            return;
        }

        $chatId = (string) ($message['chat']['id'] ?? '');
        $text = trim($message['text'] ?? '');
        $senderName = $message['from']['first_name'] ?? 'Owner';

        // Security check: restrict commands to owner chat ID if configured
        if (!empty($this->ownerChatId) && (string) $this->ownerChatId !== $chatId) {
            $this->sendMessage($chatId, "⛔ <b>Akses Ditolak</b>\nMaaf, bot ini khusus untuk manajemen internal <b>Erles Bakery ERP</b>.");
            return;
        }

        $command = strtolower(explode(' ', $text)[0]);
        $argument = trim(substr($text, strlen($command)));

        switch ($command) {
            case '/start':
            case '/help':
            case '/bantuan':
            case 'help':
            case 'bantuan':
                $this->sendHelpMessage($chatId, $senderName);
                break;

            case '/rekap':
            case '/omzet':
            case '/keuangan':
            case 'rekap':
            case 'omzet':
            case 'keuangan':
                $this->sendFinanceRecap($chatId, $argument);
                break;

            case '/pesanan':
            case '/antrean':
            case '/order':
            case 'pesanan':
            case 'antrean':
                $this->sendActiveOrders($chatId);
                break;

            case '/cek':
            case 'cek':
                $this->sendOrderDetail($chatId, $argument);
                break;

            case '/stok':
            case '/produk':
            case 'stok':
            case 'produk':
                $this->sendProductStock($chatId);
                break;

            case '/tagihan':
            case '/piutang':
            case 'tagihan':
            case 'piutang':
                $this->sendUnpaidOrders($chatId);
                break;

            case '/dashboard':
            case '/ringkasan':
            case 'dashboard':
            case 'ringkasan':
                $this->sendDashboardSummary($chatId);
                break;

            default:
                if (str_starts_with($text, '/')) {
                    $this->sendMessage($chatId, "Perintah <code>" . htmlspecialchars($command) . "</code> tidak dikenali.\nKetik /help atau /bantuan untuk melihat daftar menu.");
                } else {
                    // Friendly fallback
                    $this->sendHelpMessage($chatId, $senderName);
                }
                break;
        }
    }

    /**
     * Menu help
     */
    protected function sendHelpMessage(string $chatId, string $name): void
    {
        $msg = "👋 <b>Halo, " . htmlspecialchars($name) . "!</b>\n"
            . "Selamat datang di <b>Erles Bakery Assistant</b> 🍞\n\n"
            . "Berikut daftar perintah yang dapat Anda gunakan:\n\n"
            . "📊 <b>KEUANGAN & OMZET</b>\n"
            . "• /rekap - Rekap pemasukan, pengeluaran & laba hari ini\n"
            . "• /rekap kemarin - Rekap keuangan hari kemarin\n"
            . "• /dashboard - Ringkasan eksekutif penjualan toko\n\n"
            . "📦 <b>PESANAN & PELANGGAN</b>\n"
            . "• /pesanan - Daftar pesanan aktif yang perlu diproses\n"
            . "• /tagihan - Daftar pesanan yang belum lunas / masih DP\n"
            . "• /cek &lt;kode&gt; - Rincian pesanan (cth: <code>/cek ORD-20261102-0008</code>)\n\n"
            . "🥖 <b>KATALOG & INVENTARIS</b>\n"
            . "• /stok - Cek ketersediaan stok seluruh produk\n\n"
            . "ℹ️ <i>Ketik perintah di atas kapan saja untuk mengecek data terbaru.</i>";

        $this->sendMessage($chatId, $msg);
    }

    /**
     * Rekap Keuangan
     */
    protected function sendFinanceRecap(string $chatId, string $argument = ''): void
    {
        $date = Carbon::today();
        if (strtolower($argument) === 'kemarin') {
            $date = Carbon::yesterday();
        } elseif (!empty($argument) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $argument)) {
            $date = Carbon::parse($argument);
        }

        $dateStr = $date->format('Y-m-d');
        $displayDate = $date->format('d/m/Y');

        $transactions = FinanceTransaction::whereDate('tanggal', $dateStr)->get();
        $pemasukan = (float) $transactions->where('tipe', 'pemasukan')->sum('nominal');
        $pengeluaran = (float) $transactions->where('tipe', 'pengeluaran')->sum('nominal');
        $laba = $pemasukan - $pengeluaran;

        $ordersToday = Order::whereDate('created_at', $dateStr)->get();
        $totalOrders = $ordersToday->count();
        $paidOrders = $ordersToday->where('payment_status', 'paid')->count();
        $partialOrders = $ordersToday->where('payment_status', 'partial')->count();
        $unpaidOrders = $ordersToday->where('payment_status', 'unpaid')->count();

        $pemasukanFmt = 'Rp ' . number_format($pemasukan, 0, ',', '.');
        $pengeluaranFmt = 'Rp ' . number_format($pengeluaran, 0, ',', '.');
        $labaFmt = 'Rp ' . number_format($laba, 0, ',', '.');
        $labaEmoji = $laba >= 0 ? '🟢' : '🔴';

        $msg = "📊 <b>REKAP KEUANGAN ERLES BAKERY</b>\n"
            . "📅 <b>Tanggal:</b> {$displayDate}\n"
            . "━━━━━━━━━━━━━━━━━━━━\n"
            . "💰 <b>Pemasukan:</b> {$pemasukanFmt}\n"
            . "💸 <b>Pengeluaran:</b> {$pengeluaranFmt}\n"
            . "{$labaEmoji} <b>Laba / Rugi Bersih:</b> <b>{$labaFmt}</b>\n"
            . "━━━━━━━━━━━━━━━━━━━━\n"
            . "📦 <b>Statistik Pesanan:</b>\n"
            . "• Total Pesanan Masuk: <b>{$totalOrders}</b>\n"
            . "  - Lunas: {$paidOrders}\n"
            . "  - DP / Sebagian: {$partialOrders}\n"
            . "  - Belum Bayar: {$unpaidOrders}\n\n"
            . "💡 <i>Ketik /dashboard untuk melihat total omzet bulan ini.</i>";

        $this->sendMessage($chatId, $msg);
    }

    /**
     * Daftar Pesanan Aktif
     */
    protected function sendActiveOrders(string $chatId): void
    {
        $orders = Order::whereIn('status', ['pending', 'confirmed', 'processing', 'ready'])
            ->latest('id')
            ->take(10)
            ->get();

        if ($orders->isEmpty()) {
            $this->sendMessage($chatId, "✅ <b>Tidak ada antrean pesanan aktif!</b>\nSemua pesanan sudah selesai atau belum ada pesanan baru.");
            return;
        }

        $list = "";
        $statusLabels = [
            'pending' => '⏳ Menunggu',
            'confirmed' => '📋 Dikonfirmasi',
            'processing' => '👩‍🍳 Diproses',
            'ready' => '📦 Siap Ambil',
        ];

        foreach ($orders as $i => $order) {
            $num = $i + 1;
            $kode = htmlspecialchars($order->kode_pesanan);
            $nama = htmlspecialchars($order->customer_name);
            $total = 'Rp ' . number_format((float) $order->total_price, 0, ',', '.');
            $stLabel = $statusLabels[$order->status] ?? $order->status;
            $bayar = $order->payment_status === 'paid' ? 'Lunas' : ($order->payment_status === 'partial' ? 'DP' : 'Belum Bayar');

            $list .= "<b>{$num}. <code>{$kode}</code></b>\n"
                   . "   👤 {$nama} | {$total}\n"
                   . "   Status: {$stLabel} [{$bayar}]\n\n";
        }

        $msg = "📋 <b>DAFTAR ANTREAN PESANAN AKTIF</b> (" . $orders->count() . " pesanan)\n\n"
            . $list
            . "🔍 <i>Ketik <code>/cek &lt;kode_pesanan&gt;</code> untuk rincian detail.</i>";

        $this->sendMessage($chatId, $msg);
    }

    /**
     * Detail Satu Pesanan
     */
    protected function sendOrderDetail(string $chatId, string $query): void
    {
        if (empty($query)) {
            $this->sendMessage($chatId, "⚠️ Masukkan kode pesanan yang ingin dicek.\nContoh: <code>/cek ORD-20261102-0008</code>");
            return;
        }

        $order = Order::with('items.product')->where('kode_pesanan', 'like', "%{$query}%")->first();
        if (!$order) {
            $this->sendMessage($chatId, "❌ Pesanan dengan kode <code>" . htmlspecialchars($query) . "</code> tidak ditemukan.");
            return;
        }

        $kode = htmlspecialchars($order->kode_pesanan);
        $nama = htmlspecialchars($order->customer_name);
        $telp = htmlspecialchars($order->customer_phone);
        $alamat = htmlspecialchars($order->alamat ?? 'Ambil di Toko');
        $tglAmbil = $order->tanggal_ambil ? Carbon::parse($order->tanggal_ambil)->format('d/m/Y') : '-';
        $total = 'Rp ' . number_format((float) $order->total_price, 0, ',', '.');
        $terbayar = 'Rp ' . number_format((float) $order->paid_amount, 0, ',', '.');
        $sisa = 'Rp ' . number_format((float) $order->sisa_pembayaran, 0, ',', '.');

        $items = "";
        foreach ($order->items as $it) {
            $namaProd = htmlspecialchars($it->product?->nama ?? 'Produk');
            $sub = 'Rp ' . number_format((float) $it->subtotal, 0, ',', '.');
            $items .= "• {$namaProd} ({$it->qty}x) = {$sub}\n";
        }

        $msg = "🔎 <b>DETAIL PESANAN</b> <code>{$kode}</code>\n"
            . "━━━━━━━━━━━━━━━━━━━━\n"
            . "👤 <b>Pemesan:</b> {$nama} ({$telp})\n"
            . "📍 <b>Alamat / Pengambilan:</b> {$alamat}\n"
            . "📅 <b>Tanggal Ambil:</b> {$tglAmbil}\n"
            . "🏷️ <b>Status Alur:</b> " . strtoupper($order->status) . "\n"
            . "💳 <b>Status Bayar:</b> " . strtoupper($order->payment_status) . "\n\n"
            . "<b>Daftar Item:</b>\n{$items}\n"
            . "<b>Total Tagihan:</b> <b>{$total}</b>\n"
            . "<b>Sudah Dibayar:</b> {$terbayar}\n"
            . "<b>Sisa Tagihan:</b> <b>{$sisa}</b>";

        $this->sendMessage($chatId, $msg);
    }

    /**
     * Cek Stok Produk
     */
    protected function sendProductStock(string $chatId): void
    {
        $products = Product::where('is_active', true)->orderBy('kategori')->orderBy('stok')->get();

        if ($products->isEmpty()) {
            $this->sendMessage($chatId, "Belum ada produk aktif yang terdaftar.");
            return;
        }

        $grouped = $products->groupBy('kategori');
        $text = "🥖 <b>STOK PRODUK ERLES BAKERY</b>\n\n";

        foreach ($grouped as $kategori => $items) {
            $text .= "📁 <b>Kategori: " . htmlspecialchars($kategori) . "</b>\n";
            foreach ($items as $item) {
                $nama = htmlspecialchars($item->nama);
                $harga = 'Rp ' . number_format((float) $item->harga, 0, ',', '.');
                $stok = $item->stok;
                $warning = $stok <= 10 ? ' ⚠️ (Stok Sedikit)' : '';
                $text .= "• {$nama} ({$harga}) — <b>{$stok} pcs</b>{$warning}\n";
            }
            $text .= "\n";
        }

        $this->sendMessage($chatId, $text);
    }

    /**
     * Daftar Pesanan Belum Lunas (Piutang)
     */
    protected function sendUnpaidOrders(string $chatId): void
    {
        $orders = Order::whereIn('payment_status', ['unpaid', 'partial'])
            ->where('status', '!=', 'cancelled')
            ->latest('id')
            ->get();

        if ($orders->isEmpty()) {
            $this->sendMessage($chatId, "🎉 <b>Semua pesanan sudah Lunas!</b>\nTidak ada piutang atau tagihan yang tertunda.");
            return;
        }

        $totalPiutang = $orders->sum('sisa_pembayaran');
        $totalPiutangFmt = 'Rp ' . number_format((float) $totalPiutang, 0, ',', '.');

        $list = "";
        foreach ($orders->take(10) as $i => $ord) {
            $num = $i + 1;
            $kode = htmlspecialchars($ord->kode_pesanan);
            $nama = htmlspecialchars($ord->customer_name);
            $sisa = 'Rp ' . number_format((float) $ord->sisa_pembayaran, 0, ',', '.');
            $status = $ord->payment_status === 'partial' ? 'DP' : 'Belum Bayar';

            $list .= "<b>{$num}. <code>{$kode}</code></b> — {$nama}\n"
                   . "   Sisa: <b>{$sisa}</b> ({$status})\n";
        }

        $msg = "💳 <b>DAFTAR TAGIHAN BELUM LUNAS</b>\n"
            . "Total Piutang Tertunda: <b>{$totalPiutangFmt}</b> (" . $orders->count() . " pesanan)\n\n"
            . $list . "\n"
            . "💡 <i>Ketik <code>/cek &lt;kode&gt;</code> untuk rincian pesanan.</i>";

        $this->sendMessage($chatId, $msg);
    }

    /**
     * Dashboard Ringkasan Eksekutif
     */
    protected function sendDashboardSummary(string $chatId): void
    {
        $bulanIni = Carbon::now()->format('Y-m');
        $namaBulan = Carbon::now()->isoFormat('MMMM Y') ?? Carbon::now()->format('F Y');

        $txMonth = FinanceTransaction::where('tanggal', 'like', "{$bulanIni}%")->get();
        $pemasukanBulan = (float) $txMonth->where('tipe', 'pemasukan')->sum('nominal');
        $pengeluaranBulan = (float) $txMonth->where('tipe', 'pengeluaran')->sum('nominal');
        $labaBulan = $pemasukanBulan - $pengeluaranBulan;

        $hariIni = Carbon::today()->format('Y-m-d');
        $txDay = FinanceTransaction::whereDate('tanggal', $hariIni)->get();
        $pemasukanHari = (float) $txDay->where('tipe', 'pemasukan')->sum('nominal');

        $activeOrders = Order::whereIn('status', ['pending', 'confirmed', 'processing', 'ready'])->count();
        $totalProducts = Product::where('is_active', true)->count();
        $lowStock = Product::where('is_active', true)->where('stok', '<=', 10)->count();

        $msg = "📈 <b>RINGKASAN EKSEKUTIF ERLES BAKERY</b>\n"
            . "Periode: <b>{$namaBulan}</b>\n"
            . "━━━━━━━━━━━━━━━━━━━━\n"
            . "💵 <b>Omzet Hari Ini:</b> Rp " . number_format($pemasukanHari, 0, ',', '.') . "\n"
            . "💰 <b>Omzet Bulan Ini:</b> Rp " . number_format($pemasukanBulan, 0, ',', '.') . "\n"
            . "💸 <b>Pengeluaran Bulan Ini:</b> Rp " . number_format($pengeluaranBulan, 0, ',', '.') . "\n"
            . "🟢 <b>Laba Bersih Bulan Ini:</b> <b>Rp " . number_format($labaBulan, 0, ',', '.') . "</b>\n"
            . "━━━━━━━━━━━━━━━━━━━━\n"
            . "📦 <b>Operasional:</b>\n"
            . "• Pesanan Aktif: <b>{$activeOrders}</b> pesanan\n"
            . "• Total Produk Aktif: <b>{$totalProducts}</b> item\n"
            . "• Produk Menipis (≤10): <b>{$lowStock}</b> item\n\n"
            . "<i>Gunakan /pesanan atau /stok untuk cek detail.</i>";

        $this->sendMessage($chatId, $msg);
    }
}

