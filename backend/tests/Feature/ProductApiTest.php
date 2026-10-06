<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_can_get_product_list(): void
    {
        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'nama', 'slug', 'deskripsi', 'harga', 'stok', 'kategori', 'is_active'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_public_can_filter_products_by_kategori(): void
    {
        $response = $this->getJson('/api/products?kategori=Hampers');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $items = $response->json('data');
        $this->assertCount(4, $items);
        foreach ($items as $item) {
            $this->assertEquals('Hampers', $item['kategori']);
        }
    }

    public function test_public_can_get_single_product_by_slug(): void
    {
        $product = Product::first();

        $response = $this->getJson("/api/products/{$product->slug}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'nama' => $product->nama,
                    'slug' => $product->slug,
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_create_product(): void
    {
        $response = $this->postJson('/api/products', [
            'nama' => 'Roti Baru',
            'harga' => 20000,
            'kategori' => 'Roti',
        ]);

        $response->assertStatus(401);
    }

    public function test_admin_can_create_update_and_delete_product(): void
    {
        $admin = User::where('email', 'admin@erlesbakery.com')->first();
        $token = $admin->createToken('admin_token')->plainTextToken;

        // 1. Create
        $createResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/products', [
                'nama' => 'Roti Unik Baru',
                'harga' => 35000,
                'kategori' => 'Roti',
                'deskripsi' => 'Roti lezat dengan topping unik.',
                'stok' => 50,
            ]);

        $createResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'nama' => 'Roti Unik Baru',
                    'slug' => 'roti-unik-baru',
                    'harga' => 35000,
                    'stok' => 50,
                ],
            ]);

        $productId = $createResponse->json('data.id');

        // 2. Update
        $updateResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/products/{$productId}", [
                'harga' => 38000,
                'stok' => 45,
            ]);

        $updateResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'harga' => 38000,
                    'stok' => 45,
                ],
            ]);

        // 3. Delete
        $deleteResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/products/{$productId}");

        $deleteResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('products', ['id' => $productId]);
    }

    public function test_category_crud_and_staff_access(): void
    {
        $admin = User::where('email', 'admin@erlesbakery.com')->first();
        $adminToken = $admin->createToken('admin_token')->plainTextToken;

        // 1. Create Category
        $catResponse = $this->withHeader('Authorization', "Bearer {$adminToken}")
            ->postJson('/api/categories', [
                'name' => 'Pastry',
                'description' => 'Aneka pastry artisanal',
            ]);

        $catResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Pastry',
                    'slug' => 'pastry',
                ],
            ]);

        $catId = $catResponse->json('data.id');

        // 2. Public view categories
        $listResponse = $this->getJson('/api/categories');
        $listResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        // 3. Create product with category_id
        $prodResponse = $this->withHeader('Authorization', "Bearer {$adminToken}")
            ->postJson('/api/products', [
                'nama' => 'Croissant Almond',
                'harga' => 25000,
                'category_id' => $catId,
                'stok' => 20,
            ]);

        $prodResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'nama' => 'Croissant Almond',
                    'category_id' => $catId,
                    'kategori' => 'Pastry',
                ],
            ]);

        // 4. Delete category with products should fail (422)
        $delFail = $this->withHeader('Authorization', "Bearer {$adminToken}")
            ->deleteJson("/api/categories/{$catId}");
        $delFail->assertStatus(422);
    }

    public function test_admin_and_staff_can_adjust_product_stock(): void
    {
        $staff = User::where('role', 'karyawan')->first() ?? User::factory()->staff()->create();
        $staffToken = $staff->createToken('staff_token')->plainTextToken;

        $product = Product::first();
        $initialStock = $product->stok;

        // 1. Tambah stok (+10)
        $resAdd = $this->withHeader('Authorization', "Bearer {$staffToken}")
            ->postJson("/api/products/{$product->id}/adjust-stock", [
                'jumlah' => 10,
                'tipe' => 'tambah',
                'catatan' => 'Restock pagi',
            ]);
        $resAdd->assertStatus(200)
            ->assertJsonPath('data.stok', $initialStock + 10);

        // 2. Kurang stok (-5)
        $resSub = $this->withHeader('Authorization', "Bearer {$staffToken}")
            ->postJson("/api/products/{$product->id}/adjust-stock", [
                'jumlah' => 5,
                'tipe' => 'kurang',
                'catatan' => 'Rusak/rusak saat display',
            ]);
        $resSub->assertStatus(200)
            ->assertJsonPath('data.stok', $initialStock + 5);

        // 3. Kurang melebihi stok -> 422
        $resOver = $this->withHeader('Authorization', "Bearer {$staffToken}")
            ->postJson("/api/products/{$product->id}/adjust-stock", [
                'jumlah' => 999999,
                'tipe' => 'kurang',
            ]);
        $resOver->assertStatus(422)
            ->assertJsonPath('success', false);

        // 4. Set stok ke 30
        $resSet = $this->withHeader('Authorization', "Bearer {$staffToken}")
            ->postJson("/api/products/{$product->id}/adjust-stock", [
                'jumlah' => 30,
                'tipe' => 'set',
            ]);
        $resSet->assertStatus(200)
            ->assertJsonPath('data.stok', 30);
    }
}
