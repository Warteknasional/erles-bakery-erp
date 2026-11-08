<?php

namespace App\Http\Controllers;

use App\Jobs\SendTelegramNotificationJob;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\WaSession;
use App\Services\WahaClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WahaWebhookController extends Controller
{
    public function __construct(
        protected WahaClient $waha
    ) {}

    /**
     * Handle incoming WAHA webhook events.
     */
    public function handle(Request $request): JsonResponse
    {
        // 1. Verify webhook secret using timing-safe hash_equals
        $configuredSecret = config('services.waha.webhook_secret', env('WAHA_WEBHOOK_SECRET'));
        if (empty($configuredSecret)) {
            return response()->json(['message' => 'Webhook secret not configured'], 401);
        }

        $incomingSecret = $request->header('X-Webhook-Secret')
            ?? $request->header('X-Api-Key')
            ?? $request->bearerToken()
            ?? $request->query('secret');

        $isValid = false;
        if ($incomingSecret && hash_equals((string) $configuredSecret, (string) $incomingSecret)) {
            $isValid = true;
        } elseif ($request->header('X-Hmac-Sha256')) {
            $signature = (string) $request->header('X-Hmac-Sha256');
            $computed = hash_hmac('sha256', $request->getContent(), (string) $configuredSecret);
            if (hash_equals($computed, $signature) || hash_equals("sha256={$computed}", $signature)) {
                $isValid = true;
            }
        }

        if (!$isValid) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // 2. Extract payload and message data
        $payload = $request->input('payload') ?? $request->all();

        // Ignore messages sent by self
        $fromMe = (bool) ($payload['fromMe'] ?? false);
        if ($fromMe) {
            return response()->json(['status' => 'ignored_from_me']);
        }

        // Ignore group messages
        $from = $payload['from'] ?? '';
        if (empty($from) || str_contains($from, '@g.us') || ($payload['isGroup'] ?? false)) {
            return response()->json(['status' => 'ignored_group_or_empty']);
        }

        $body = trim($payload['body'] ?? $payload['text'] ?? '');
        if ($body === '') {
            return response()->json(['status' => 'ignored_empty_body']);
        }

        // Extract clean phone number
        $cleanPhone = preg_replace('/@.*$/', '', $from);
        $cleanPhone = preg_replace('/\D/', '', $cleanPhone);

        // 3. Process conversational state machine
        $this->processMessage($from, $cleanPhone, $body);

        return response()->json(['status' => 'success']);
    }

    /**
     * Process incoming message according to stateful conversation flow.
     */
    protected function processMessage(string $from, string $phone, string $body): void
    {
        $lower = strtolower(trim($body));

        // Global: Batal
        if ($lower === 'batal') {
            WaSession::where('phone', $phone)->delete();
            $this->waha->sendText(
                $from,
                "❌ *Pemesanan Dibatalkan*\n\nSesi Anda telah dibatalkan. Ketik *menu* untuk melihat daftar produk atau *pesan* untuk memulai pemesanan baru."
            );
            return;
        }

        // 1) Menu command
        if ($lower === 'menu') {
            WaSession::where('phone', $phone)->delete();
            $products = Product::where('is_active', true)->orderBy('id')->get();

            $menuText = "📋 *DAFTAR MENU ERLES BAKERY*\n\n";
            $i = 1;
            foreach ($products as $p) {
                $hargaFormatted = number_format((float) $p->harga, 0, ',', '.');
                $menuText .= "{$i}. *{$p->nama}* — Rp {$hargaFormatted}\n";
                $i++;
            }
            $menuText .= "\nKetik *pesan* untuk mulai memesan roti via WhatsApp!";

            $this->waha->sendText($from, $menuText);
            return;
        }

        // 4) Cek status pesanan: "cek ERL-xxxx"
        if (str_starts_with($lower, 'cek')) {
            $code = trim(preg_replace('/^cek\s*/i', '', $body));
            if (empty($code)) {
                $this->waha->sendText($from, "Format pengecekan: *cek <kode_pesanan>*\nContoh: *cek ERL-20261101-1234*");
                return;
            }

            $order = Order::where('kode_pesanan', 'ILIKE', $code)->first()
                ?? Order::where('kode_pesanan', 'like', $code)->first();

            if ($order) {
                $totalFormatted = 'Rp ' . number_format((float) $order->total_price, 0, ',', '.');
                $statusFormatted = strtoupper($order->status);
                $bayarFormatted = strtoupper($order->payment_status ?? 'UNPAID');

                $reply = "📦 *STATUS PESANAN*\n\n"
                    . "Kode Pesanan: <code>{$order->kode_pesanan}</code>\n"
                    . "Nama: {$order->customer_name}\n"
                    . "Status Pesanan: *{$statusFormatted}*\n"
                    . "Status Pembayaran: *{$bayarFormatted}*\n"
                    . "Total: *{$totalFormatted}*";

                $this->waha->sendText($from, $reply);
            } else {
                $this->waha->sendText($from, "Pesanan dengan kode *{$code}* tidak ditemukan. Mohon pastikan kode pesanan Anda sudah benar.");
            }
            return;
        }

        // 2) Pesan command: Start order flow
        if ($lower === 'pesan') {
            $products = Product::where('is_active', true)->orderBy('id')->get();
            $productMap = [];

            $msg = "🍞 *FORM PEMESANAN ERLES BAKERY*\n\nSilakan pilih nomor atau nama produk yang ingin dipesan:\n\n";
            $i = 1;
            foreach ($products as $p) {
                $hargaFormatted = number_format((float) $p->harga, 0, ',', '.');
                $msg .= "{$i}. {$p->nama} (Rp {$hargaFormatted})\n";
                $productMap[$i] = [
                    'id' => $p->id,
                    'nama' => $p->nama,
                    'harga' => (float) $p->harga,
                ];
                $i++;
            }
            $msg .= "\nKetik *nomor* produk pilihan Anda (contoh: *1*):";

            WaSession::updateOrCreate(
                ['phone' => $phone],
                [
                    'step' => 'awaiting_product',
                    'payload' => [
                        'items' => [],
                        'product_map' => $productMap,
                    ],
                    'expires_at' => now()->addMinutes(30),
                ]
            );

            $this->waha->sendText($from, $msg);
            return;
        }

        // Retrieve existing session
        $session = WaSession::where('phone', $phone)->first();
        if ($session && $session->isExpired()) {
            $session->delete();
            $session = null;
        }

        // If no active session, send help message
        if (!$session) {
            $help = "Halo! Selamat datang di *Erles Bakery* 🍞\n\n"
                . "Perintah yang dapat digunakan:\n"
                . "• *menu* : Melihat daftar roti & kue\n"
                . "• *pesan* : Membuat pesanan baru\n"
                . "• *cek <kode>* : Cek status pesanan (contoh: cek ERL-20261101-1234)\n"
                . "• *batal* : Membatalkan sesi pemesanan";

            $this->waha->sendText($from, $help);
            return;
        }

        // Handle steps in active session
        $payloadData = $session->payload ?? [];

        switch ($session->step) {
            case 'awaiting_product':
                $productMap = $payloadData['product_map'] ?? [];
                $selected = null;

                // Match by number
                if (is_numeric($body) && isset($productMap[(int) $body])) {
                    $selected = $productMap[(int) $body];
                } else {
                    // Match by name
                    foreach ($productMap as $p) {
                        if (stripos($p['nama'], $body) !== false) {
                            $selected = $p;
                            break;
                        }
                    }
                }

                if (!$selected) {
                    $this->waha->sendText($from, "Produk tidak ditemukan. Silakan masukkan nomor atau nama produk yang tertera di menu:");
                    return;
                }

                $payloadData['current_product'] = $selected;
                $session->update([
                    'step' => 'awaiting_qty',
                    'payload' => $payloadData,
                    'expires_at' => now()->addMinutes(30),
                ]);

                $this->waha->sendText($from, "Berapa jumlah (qty) untuk *{$selected['nama']}*? (Ketik angka, contoh: *2*)");
                break;

            case 'awaiting_qty':
                $qty = (int) preg_replace('/\D/', '', $body);
                if ($qty <= 0) {
                    $this->waha->sendText($from, "Jumlah pesanan harus berupa angka minimal 1. Silakan ketik jumlah yang valid:");
                    return;
                }

                $currentProduct = $payloadData['current_product'] ?? null;
                if (!$currentProduct) {
                    $session->delete();
                    $this->waha->sendText($from, "Terjadi kesalahan sesi. Silakan ketik *pesan* untuk mengulang.");
                    return;
                }

                $items = $payloadData['items'] ?? [];
                $items[] = [
                    'product_id' => $currentProduct['id'],
                    'nama' => $currentProduct['nama'],
                    'harga' => $currentProduct['harga'],
                    'qty' => $qty,
                    'subtotal' => $currentProduct['harga'] * $qty,
                ];

                $payloadData['items'] = $items;
                unset($payloadData['current_product']);

                $session->update([
                    'step' => 'awaiting_name',
                    'payload' => $payloadData,
                    'expires_at' => now()->addMinutes(30),
                ]);

                $this->waha->sendText($from, "Produk dicatat! Selanjutnya, silakan masukkan *Nama Lengkap* Anda:");
                break;

            case 'awaiting_name':
                $name = trim($body);
                if (strlen($name) < 2) {
                    $this->waha->sendText($from, "Nama terlalu pendek. Masukkan nama lengkap Anda:");
                    return;
                }

                $payloadData['customer_name'] = $name;
                $session->update([
                    'step' => 'awaiting_pickup_date',
                    'payload' => $payloadData,
                    'expires_at' => now()->addMinutes(30),
                ]);

                $this->waha->sendText($from, "Kapan pesanan ingin diambil atau dikirim? (Contoh: *besok*, *2026-11-05*, atau *hari ini*)");
                break;

            case 'awaiting_pickup_date':
                $dateStr = trim($body);
                $payloadData['tanggal_ambil'] = $dateStr;

                // Build summary
                $total = 0;
                $itemsList = '';
                foreach ($payloadData['items'] as $it) {
                    $subFmt = number_format((float) $it['subtotal'], 0, ',', '.');
                    $itemsList .= "• {$it['nama']} x {$it['qty']} (Rp {$subFmt})\n";
                    $total += $it['subtotal'];
                }

                $totalFmt = number_format($total, 0, ',', '.');

                $summary = "📋 *RINGKASAN PESANAN ANDA*\n\n"
                    . "Nama Pelanggan: *{$payloadData['customer_name']}*\n"
                    . "Nomor WA: *{$phone}*\n"
                    . "Tanggal Ambil/Kirim: *{$dateStr}*\n\n"
                    . "Daftar Item:\n{$itemsList}\n"
                    . "Total Tagihan: *Rp {$totalFmt}*\n\n"
                    . "Ketik *ya* untuk konfirmasi pesanan, atau *batal* untuk membatalkan.";

                $session->update([
                    'step' => 'awaiting_confirmation',
                    'payload' => $payloadData,
                    'expires_at' => now()->addMinutes(30),
                ]);

                $this->waha->sendText($from, $summary);
                break;

            case 'awaiting_confirmation':
                if ($lower === 'ya') {
                    // Create Order using identical logic as OrderController@store
                    $order = $this->createOrderFromWa($phone, $payloadData);

                    $session->delete();

                    $totalFmt = number_format((float) $order->total_price, 0, ',', '.');
                    $successMsg = "✅ *PESANAN ANDA BERHASIL DIBUAT!*\n\n"
                        . "Kode Pesanan: *{$order->kode_pesanan}*\n"
                        . "Total Tagihan: *Rp {$totalFmt}*\n"
                        . "Status: *Perlu Konfirmasi*\n\n"
                        . "Simpan kode pesanan Anda. Untuk mengecek status pesanan sewaktu-waktu, ketik:\n"
                        . "*cek {$order->kode_pesanan}*\n\n"
                        . "Terima kasih telah memesan di Erles Bakery! 🙏";

                    $this->waha->sendText($from, $successMsg);
                } else {
                    $this->waha->sendText($from, "Ketik *ya* untuk konfirmasi pemesanan, atau ketik *batal* untuk membatalkan.");
                }
                break;

            default:
                $session->delete();
                $this->waha->sendText($from, "Sesi tidak dikenal. Ketik *pesan* untuk memulai pemesanan baru.");
                break;
        }
    }

    /**
     * Create order from WhatsApp session data.
     * Reuses identical logic to OrderController@store:
     * - Server-side price calculation
     * - Customer auto-creation
     * - Order code generation
     * - Source marked as 'whatsapp'
     * - Telegram notification to owner/admin
     */
    protected function createOrderFromWa(string $phone, array $payloadData): Order
    {
        return DB::transaction(function () use ($phone, $payloadData) {
            $productIds = collect($payloadData['items'])->pluck('product_id')->unique();
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

            $customer = Customer::findOrCreateByPhone(
                $phone,
                $payloadData['customer_name'] ?? 'Pelanggan WhatsApp',
                'Pemesanan via WhatsApp Bot'
            );

            // Parse optional pickup date
            $pickupDate = null;
            if (!empty($payloadData['tanggal_ambil'])) {
                try {
                    $pickupDate = now()->parse($payloadData['tanggal_ambil'])->toDateString();
                } catch (\Throwable) {
                    $pickupDate = now()->toDateString();
                }
            }

            $order = Order::create([
                'customer_id' => $customer->id,
                'kode_pesanan' => Order::generateKodePesanan(),
                'customer_name' => $payloadData['customer_name'] ?? 'Pelanggan WhatsApp',
                'customer_phone' => $phone,
                'alamat' => 'Pemesanan via WhatsApp',
                'catatan' => 'Pemesanan otomatis melalui WhatsApp Bot',
                'tanggal_ambil' => $pickupDate,
                'status' => 'pending',
                'source' => 'whatsapp',
                'total_price' => 0,
            ]);

            $totalPrice = 0;

            foreach ($payloadData['items'] as $itemData) {
                $product = $products->get($itemData['product_id']);
                if (!$product) {
                    continue;
                }

                $qty = (int) $itemData['qty'];
                $unitPrice = (float) $product->harga;
                $subtotal = $unitPrice * $qty;
                $totalPrice += $subtotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);
            }

            $order->update(['total_price' => $totalPrice]);
            $customer->increment('total_orders');
            $customer->increment('total_spent', $totalPrice);

            $order->load(['items.product', 'customer']);

            // Trigger Telegram notification to owner/admin
            dispatch(new SendTelegramNotificationJob('order', $order->id))->afterCommit();

            return $order;
        });
    }
}

