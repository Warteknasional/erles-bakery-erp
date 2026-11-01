<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\WaSession;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WahaWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        Config::set('services.waha.url', 'http://waha:3000');
        Config::set('services.waha.session', 'default');
        Config::set('services.waha.api_key', 'test_api_key');
        Config::set('services.waha.webhook_secret', 'test_secret_token_123');
    }

    protected function postWebhook(array $data = [], array $headers = [])
    {
        $defaultHeaders = [
            'X-Webhook-Secret' => 'test_secret_token_123',
        ];

        return $this->withHeaders(array_merge($defaultHeaders, $headers))
            ->postJson('/api/webhooks/waha', $data);
    }

    public function test_menu_command_sends_active_product_catalog(): void
    {
        Http::fake([
            'http://waha:3000/*' => Http::response(['success' => true], 200),
        ]);

        $response = $this->postWebhook([
            'from' => '628123456789@c.us',
            'body' => 'menu',
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        Http::assertSent(function ($request) {
            $isCorrectEndpoint = str_contains($request->url(), 'waha:3000/api/sendText');
            $hasChatId = ($request['chatId'] ?? '') === '628123456789@c.us';
            $text = $request['text'] ?? '';
            $hasMenuHeader = str_contains($text, 'DAFTAR MENU ERLES BAKERY');
            $hasProduct = str_contains($text, 'Roti Tawar Gandum');

            return $isCorrectEndpoint && $hasChatId && $hasMenuHeader && $hasProduct;
        });
    }

    public function test_full_order_placement_flow_via_whatsapp(): void
    {
        Config::set('services.telegram.bot_token', 'mock_tg_token');
        Config::set('services.telegram.chat_id', '-1009999999');

        Http::fake([
            'http://waha:3000/*' => Http::response(['success' => true], 200),
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $phone = '628123456789';
        $chatId = '628123456789@c.us';

        // 1. Send "pesan"
        $res1 = $this->postWebhook([
            'from' => $chatId,
            'body' => 'pesan',
        ]);
        $res1->assertStatus(200);

        $session = WaSession::where('phone', $phone)->first();
        $this->assertNotNull($session);
        $this->assertEquals('awaiting_product', $session->step);

        // 2. Select product "1"
        $res2 = $this->postWebhook([
            'from' => $chatId,
            'body' => '1',
        ]);
        $res2->assertStatus(200);

        $session->refresh();
        $this->assertEquals('awaiting_qty', $session->step);

        // 3. Enter qty "2"
        $res3 = $this->postWebhook([
            'from' => $chatId,
            'body' => '2',
        ]);
        $res3->assertStatus(200);

        $session->refresh();
        $this->assertEquals('awaiting_name', $session->step);

        // 4. Enter customer name "Farhan Maulana"
        $res4 = $this->postWebhook([
            'from' => $chatId,
            'body' => 'Farhan Maulana',
        ]);
        $res4->assertStatus(200);

        $session->refresh();
        $this->assertEquals('awaiting_pickup_date', $session->step);

        // 5. Enter pickup date "2026-11-05"
        $res5 = $this->postWebhook([
            'from' => $chatId,
            'body' => '2026-11-05',
        ]);
        $res5->assertStatus(200);

        $session->refresh();
        $this->assertEquals('awaiting_confirmation', $session->step);

        // 6. Confirm order with "ya"
        $res6 = $this->postWebhook([
            'from' => $chatId,
            'body' => 'ya',
        ]);
        $res6->assertStatus(200);

        // Session should be cleared
        $this->assertNull(WaSession::where('phone', $phone)->first());

        // Order must be saved with source = 'whatsapp'
        $order = Order::where('customer_phone', $phone)->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertEquals('whatsapp', $order->source);
        $this->assertEquals('Farhan Maulana', $order->customer_name);
        $this->assertGreaterThan(0, $order->total_price);
        $this->assertCount(1, $order->items);

        // Verify WA confirmation was sent to customer
        Http::assertSent(function ($request) use ($chatId, $order) {
            $isWa = str_contains($request->url(), 'waha:3000/api/sendText');
            $hasChat = ($request['chatId'] ?? '') === $chatId;
            $hasSuccess = str_contains($request['text'] ?? '', 'PESANAN ANDA BERHASIL DIBUAT');
            $hasCode = str_contains($request['text'] ?? '', $order->kode_pesanan);

            return $isWa && $hasChat && $hasSuccess && $hasCode;
        });

        // Verify Telegram notification was also dispatched/sent to owner
        Http::assertSent(function ($request) use ($order) {
            $isTg = str_contains($request->url(), 'api.telegram.org');
            $hasTgText = str_contains($request['text'] ?? '', $order->kode_pesanan);

            return $isTg && $hasTgText;
        });
    }

    public function test_check_order_status_command(): void
    {
        Http::fake([
            'http://waha:3000/*' => Http::response(['success' => true], 200),
        ]);

        $order = Order::factory()->create([
            'kode_pesanan' => 'ORD-20261101-7777',
            'customer_name' => 'Siti Rahma',
            'customer_phone' => '081234567890',
            'status' => 'processing',
            'total_price' => 75000,
            'source' => 'whatsapp',
        ]);

        $response = $this->postWebhook([
            'from' => '6281234567890@c.us',
            'body' => 'cek ORD-20261101-7777',
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) use ($order) {
            $isWa = str_contains($request->url(), 'waha:3000/api/sendText');
            $text = $request['text'] ?? '';
            $hasCode = str_contains($text, $order->kode_pesanan);
            $hasStatus = str_contains($text, 'PROCESSING');
            $hasName = str_contains($text, 'Siti Rahma');

            return $isWa && $hasCode && $hasStatus && $hasName;
        });
    }

    public function test_invalid_webhook_secret_is_rejected_with_401(): void
    {
        Config::set('services.waha.webhook_secret', 'my_secret_token_123');

        // Request with wrong secret
        $response = $this->withHeaders([
            'X-Webhook-Secret' => 'wrong_secret',
        ])->postJson('/api/webhooks/waha', [
            'from' => '628123456789@c.us',
            'body' => 'menu',
        ]);

        $response->assertStatus(401);

        // Request with correct secret
        $responseSuccess = $this->withHeaders([
            'X-Webhook-Secret' => 'my_secret_token_123',
        ])->postJson('/api/webhooks/waha', [
            'from' => '628123456789@c.us',
            'body' => 'menu',
        ]);

        $responseSuccess->assertStatus(200);
    }

    public function test_empty_webhook_secret_in_config_is_rejected_with_401(): void
    {
        Config::set('services.waha.webhook_secret', null);

        $response = $this->postJson('/api/webhooks/waha', [
            'from' => '628123456789@c.us',
            'body' => 'menu',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Webhook secret not configured']);
    }

    public function test_webhook_rate_limiter_blocks_121st_request(): void
    {
        Http::fake([
            'http://waha:3000/*' => Http::response(['success' => true], 200),
        ]);

        for ($i = 1; $i <= 120; $i++) {
            $res = $this->postWebhook([
                'from' => '628123456789@c.us',
                'body' => 'menu',
            ]);
            $res->assertStatus(200);
        }

        // 121st request from same IP must be throttled with HTTP 429
        $res121 = $this->postWebhook([
            'from' => '628123456789@c.us',
            'body' => 'menu',
        ]);

        $res121->assertStatus(429);
    }

    public function test_waha_error_does_not_fail_order_status_update(): void
    {
        // Simulate WAHA failure
        Http::fake([
            'http://waha:3000/*' => Http::response(['error' => 'WAHA service down'], 500),
        ]);

        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $order = Order::factory()->create([
            'kode_pesanan' => 'ORD-20261101-8888',
            'customer_name' => 'Budi Santoso',
            'customer_phone' => '081298765432',
            'status' => 'pending',
            'source' => 'whatsapp',
        ]);

        $response = $this->putJson("/api/orders/{$order->id}/status", [
            'status' => 'processing',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('processing', $order->fresh()->status);
    }

    public function test_waha_notification_sent_on_payment_for_whatsapp_orders(): void
    {
        Http::fake([
            'http://waha:3000/*' => Http::response(['success' => true], 200),
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $order = Order::factory()->create([
            'kode_pesanan' => 'ORD-20261101-9991',
            'customer_name' => 'Ani Lestari',
            'customer_phone' => '081298765432',
            'total_price' => 50000,
            'status' => 'pending',
            'source' => 'whatsapp',
        ]);

        $response = $this->postJson("/api/orders/{$order->id}/payments", [
            'nominal' => 50000,
            'metode' => 'transfer',
            'tipe' => 'lunas',
            'tanggal' => now()->toDateString(),
        ]);

        $response->assertStatus(201);

        Http::assertSent(function ($request) {
            $isWa = str_contains($request->url(), 'waha:3000/api/sendText');
            $text = $request['text'] ?? '';
            $hasNominal = str_contains($text, 'Rp 50.000');
            $hasOrderCode = str_contains($text, 'ORD-20261101-9991');

            return $isWa && $hasNominal && $hasOrderCode;
        });
    }

    public function test_waha_notification_not_sent_for_web_orders(): void
    {
        Http::fake([
            'http://waha:3000/*' => Http::response(['success' => true], 200),
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $order = Order::factory()->create([
            'kode_pesanan' => 'ORD-20261101-9992',
            'customer_name' => 'Web Customer',
            'customer_phone' => '081298765432',
            'total_price' => 50000,
            'status' => 'pending',
            'source' => 'web',
        ]);

        $response = $this->putJson("/api/orders/{$order->id}/status", [
            'status' => 'processing',
        ]);

        $response->assertStatus(200);

        // WAHA should NOT be called for customer notification
        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'waha:3000/api/sendText');
        });
    }

    public function test_waha_ignores_self_messages_and_group_messages(): void
    {
        // 1. From self
        $resSelf = $this->postWebhook([
            'fromMe' => true,
            'from' => '628123456789@c.us',
            'body' => 'menu',
        ]);
        $resSelf->assertStatus(200)
            ->assertJson(['status' => 'ignored_from_me']);

        // 2. From group
        $resGroup = $this->postWebhook([
            'fromMe' => false,
            'from' => '12036302@g.us',
            'body' => 'menu',
        ]);
        $resGroup->assertStatus(200)
            ->assertJson(['status' => 'ignored_group_or_empty']);
    }
}
