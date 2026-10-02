<?php

namespace Tests\Feature;

use App\Models\FinanceTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_can_create_order_with_server_side_total_calculation(): void
    {
        $p1 = Product::where('nama', 'Roti Tawar Gandum')->first(); // 25000
        $p2 = Product::where('nama', 'Brownies Panggang')->first();  // 45000

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Ahmad Dani',
            'customer_phone' => '081234567899',
            'alamat' => 'Jl. Thamrin No. 1, Jakarta',
            'tanggal_ambil' => now()->addDays(2)->toDateString(),
            'catatan' => 'Minta bon basah',
            'items' => [
                ['product_id' => $p1->id, 'qty' => 2], // 50,000
                ['product_id' => $p2->id, 'qty' => 1], // 45,000
            ],
        ]);

        $expectedTotal = (25000 * 2) + (45000 * 1); // 95,000

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'customer_name' => 'Ahmad Dani',
                    'customer_phone' => '081234567899',
                    'status' => 'pending',
                    'total_price' => $expectedTotal,
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Ahmad Dani',
            'total_price' => $expectedTotal,
        ]);
    }

    public function test_order_creation_validates_required_fields(): void
    {
        $response = $this->postJson('/api/orders', [
            'customer_name' => '',
            'items' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_name', 'customer_phone', 'items']);
    }

    public function test_admin_can_view_orders_and_filter_by_status(): void
    {
        $admin = User::where('email', 'admin@erlesbakery.com')->first();
        $token = $admin->createToken('admin_token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/orders?status=pending');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $orders = $response->json('data');
        foreach ($orders as $order) {
            $this->assertEquals('pending', $order['status']);
        }
    }

    public function test_admin_updating_order_status_to_selesai_creates_finance_transaction(): void
    {
        $admin = User::where('email', 'admin@erlesbakery.com')->first();
        $token = $admin->createToken('admin_token')->plainTextToken;

        $pendingOrder = Order::where('status', 'pending')->first();
        $this->assertNotNull($pendingOrder);

        $initialFinanceCount = FinanceTransaction::where('order_id', $pendingOrder->id)->count();
        $this->assertEquals(0, $initialFinanceCount);

        // Update status to 'selesai'
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/orders/{$pendingOrder->id}/status", [
                'status' => 'selesai',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $pendingOrder->id,
                    'status' => 'selesai',
                ],
            ]);

        // Check that finance transaction was automatically created
        $this->assertDatabaseHas('finance_transactions', [
            'order_id' => $pendingOrder->id,
            'tipe' => 'pemasukan',
            'nominal' => $pendingOrder->total_price,
            'kategori' => 'Penjualan',
        ]);
    }
}
