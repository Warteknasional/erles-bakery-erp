<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_telegram_notification_is_sent_when_public_order_is_created(): void
    {
        Config::set('services.telegram.bot_token', 'mock_bot_token_123');
        Config::set('services.telegram.chat_id', '-100123456789');

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $product = Product::where('nama', 'Roti Tawar Gandum')->first();

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Budi Santoso',
            'customer_phone' => '081298765432',
            'alamat' => 'Jl. Merdeka No. 10, Jakarta Pusat',
            'tanggal_ambil' => now()->addDays(1)->toDateString(),
            'catatan' => 'Jangan pakai plastik',
            'items' => [
                ['product_id' => $product->id, 'qty' => 3],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'customer_name' => 'Budi Santoso',
                ],
            ]);

        Http::assertSent(function ($request) use ($product) {
            $isCorrectEndpoint = str_contains($request->url(), 'api.telegram.org/botmock_bot_token_123/sendMessage');
            $hasChatId = $request['chat_id'] === '-100123456789';
            $isHtml = $request['parse_mode'] === 'HTML';
            $text = $request['text'];

            $hasHeader = str_contains($text, 'PESANAN BARU MASUK');
            $hasCustomer = str_contains($text, 'Budi Santoso');
            $hasProduct = str_contains($text, $product->nama);
            $hasTotal = str_contains($text, 'Total:');
            $hasMetode = str_contains($text, 'Jl. Merdeka No. 10');

            return $isCorrectEndpoint && $hasChatId && $isHtml && $hasHeader && $hasCustomer && $hasProduct && $hasTotal && $hasMetode;
        });
    }

    public function test_order_creation_succeeds_even_when_telegram_fails_or_times_out(): void
    {
        Config::set('services.telegram.bot_token', 'mock_bot_token_123');
        Config::set('services.telegram.chat_id', '-100123456789');

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Internal Server Error'], 500),
        ]);

        $product = Product::first();

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Siti Nurhaliza',
            'customer_phone' => '081344445555',
            'alamat' => null,
            'items' => [
                ['product_id' => $product->id, 'qty' => 1],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'customer_name' => 'Siti Nurhaliza',
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Siti Nurhaliza',
            'customer_phone' => '081344445555',
        ]);
    }

    public function test_telegram_notification_is_skipped_silently_when_token_or_chat_id_is_empty(): void
    {
        Config::set('services.telegram.bot_token', null);
        Config::set('services.telegram.chat_id', null);

        Http::fake();

        $product = Product::first();

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Andi Wijaya',
            'customer_phone' => '081566667777',
            'items' => [
                ['product_id' => $product->id, 'qty' => 1],
            ],
        ]);

        $response->assertStatus(201);
        Http::assertNothingSent();
    }

    public function test_telegram_notification_is_sent_when_payment_is_recorded(): void
    {
        Config::set('services.telegram.bot_token', 'mock_bot_token_123');
        Config::set('services.telegram.chat_id', '-100123456789');

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $admin = User::where('role', 'admin')->first();
        $order = Order::where('status', 'pending')->first();

        $response = $this->actingAs($admin, 'sanctum')->postJson("/api/orders/{$order->id}/payments", [
            'nominal' => 20000,
            'metode' => 'transfer',
            'tipe' => 'dp',
            'catatan' => 'DP transfer bank BCA',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        Http::assertSent(function ($request) use ($order) {
            $isCorrectEndpoint = str_contains($request->url(), 'api.telegram.org/botmock_bot_token_123/sendMessage');
            $hasChatId = $request['chat_id'] === '-100123456789';
            $text = $request['text'];

            $hasHeader = str_contains($text, 'PEMBAYARAN PESANAN DITERIMA');
            $hasKode = str_contains($text, $order->kode_pesanan);
            $hasNominal = str_contains($text, '20.000');
            $hasMetode = str_contains($text, 'TRANSFER');

            return $isCorrectEndpoint && $hasChatId && $hasHeader && $hasKode && $hasNominal && $hasMetode;
        });
    }

    public function test_payment_recording_succeeds_even_when_telegram_fails(): void
    {
        Config::set('services.telegram.bot_token', 'mock_bot_token_123');
        Config::set('services.telegram.chat_id', '-100123456789');

        Http::fake([
            'https://api.telegram.org/*' => Http::response('Server unreachable', 503),
        ]);

        $admin = User::where('role', 'admin')->first();
        $order = Order::where('status', 'pending')->first();

        $response = $this->actingAs($admin, 'sanctum')->postJson("/api/orders/{$order->id}/payments", [
            'nominal' => 15000,
            'metode' => 'cash',
            'tipe' => 'dp',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'nominal' => 15000,
        ]);
    }
}

