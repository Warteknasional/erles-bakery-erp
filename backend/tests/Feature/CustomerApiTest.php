<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_crud_customers(): void
    {
        $admin = User::where('email', 'admin@erlesbakery.com')->first();
        $token = $admin->createToken('admin_token')->plainTextToken;

        // 1. Create Customer
        $createResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/customers', [
                'name' => 'Budi Santoso',
                'phone' => '081298765432',
                'email' => 'budi@example.com',
                'address' => 'Jl. Ijen No. 10, Malang',
                'notes' => 'Pelanggan langganan roti tawar',
            ]);

        $createResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Budi Santoso',
                    'phone' => '081298765432',
                ],
            ]);

        $customerId = $createResponse->json('data.id');

        // 2. List Customers
        $listResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/customers?search=Budi');

        $listResponse->assertStatus(200)
            ->assertJson(['success' => true]);
        $this->assertCount(1, $listResponse->json('data'));

        // 3. Update Customer
        $updateResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/customers/{$customerId}", [
                'address' => 'Jl. Semeru No. 5, Malang',
            ]);

        $updateResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'address' => 'Jl. Semeru No. 5, Malang',
                ],
            ]);

        // 4. Delete Customer
        $deleteResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/customers/{$customerId}");

        $deleteResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('customers', ['id' => $customerId]);
    }

    public function test_public_order_auto_creates_customer(): void
    {
        $product = Product::first();

        $this->assertDatabaseMissing('customers', ['phone' => '089911223344']);

        $orderResponse = $this->postJson('/api/orders', [
            'customer_name' => 'Siti Aminah',
            'customer_phone' => '089911223344',
            'alamat' => 'Jl. Kawi No. 12, Malang',
            'catatan' => 'Minta plastik terpisah',
            'items' => [
                ['product_id' => $product->id, 'qty' => 2],
            ],
        ]);

        $orderResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('customers', [
            'name' => 'Siti Aminah',
            'phone' => '089911223344',
            'total_orders' => 1,
        ]);

        $customer = Customer::where('phone', '089911223344')->first();
        $this->assertNotNull($customer);
        $this->assertEquals(2 * $product->harga, (float) $customer->total_spent);
    }
}
