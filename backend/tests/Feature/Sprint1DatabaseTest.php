<?php

namespace Tests\Feature;

use App\Models\FinanceTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Sprint1DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_endpoint(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
            ]);
    }

    public function test_database_seeder_creates_all_expected_records(): void
    {
        $this->seed(DatabaseSeeder::class);

        // 1. Verify Admin User
        $admin = User::where('email', 'admin@erlesbakery.com')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->isAdmin());
        $this->assertEquals('admin', $admin->role);

        // 2. Verify Products
        $this->assertEquals(15, Product::count());
        $this->assertEquals(4, Product::kategori('Hampers')->count());
        $this->assertEquals(4, Product::kategori('Roti')->count());
        $this->assertEquals(15, Product::active()->count());

        // 3. Verify Orders & OrderItems
        $this->assertEquals(5, Order::count());
        $this->assertGreaterThan(0, OrderItem::count());

        $orders = Order::with('items')->get();
        foreach ($orders as $order) {
            $this->assertNotEmpty($order->kode_pesanan);
            $this->assertGreaterThan(0, $order->items->count());
            $calculatedTotal = $order->items->sum(fn ($item) => (float) $item->subtotal);
            $this->assertEquals($calculatedTotal, (float) $order->total_price);
        }

        // 4. Verify Finance Transactions
        $this->assertEquals(7, FinanceTransaction::count());
        $this->assertEquals(2, FinanceTransaction::tipe('pemasukan')->count());
        $this->assertEquals(5, FinanceTransaction::tipe('pengeluaran')->count());

        // Verify relationships
        $income = FinanceTransaction::whereNotNull('order_id')->first();
        $this->assertNotNull($income);
        $this->assertInstanceOf(Order::class, $income->order);
        $this->assertInstanceOf(User::class, $income->user);
    }
}
