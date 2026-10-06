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

class FinanceApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_unauthenticated_user_cannot_access_finance(): void
    {
        $response = $this->getJson('/api/finance');
        $response->assertStatus(401);

        $dashRes = $this->getJson('/api/dashboard');
        $dashRes->assertStatus(401);
    }

    public function test_admin_can_view_finance_list_with_summary(): void
    {
        $admin = User::where('email', 'admin@erlesbakery.com')->first();
        $token = $admin->createToken('admin_token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/finance');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'tipe', 'nominal', 'kategori', 'catatan', 'tanggal'],
                ],
                'meta' => [
                    'current_page',
                    'total',
                    'summary' => ['total_pemasukan', 'total_pengeluaran', 'saldo', 'omzet', 'laba'],
                ],
            ]);
    }

    public function test_admin_can_create_finance_transaction(): void
    {
        $admin = User::where('email', 'admin@erlesbakery.com')->first();
        $token = $admin->createToken('admin_token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/finance', [
                'tipe' => 'pengeluaran',
                'nominal' => 250000,
                'kategori' => 'Bahan Baku',
                'catatan' => 'Beli keju cheddar 5kg',
                'tanggal' => now()->toDateString(),
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'tipe' => 'pengeluaran',
                    'nominal' => 250000,
                    'kategori' => 'Bahan Baku',
                    'catatan' => 'Beli keju cheddar 5kg',
                ],
            ]);

        $this->assertDatabaseHas('finance_transactions', [
            'nominal' => 250000,
            'kategori' => 'Bahan Baku',
            'user_id' => $admin->id,
        ]);
    }

    public function test_admin_can_get_finance_summary_report(): void
    {
        $admin = User::where('email', 'admin@erlesbakery.com')->first();
        $token = $admin->createToken('admin_token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/finance/summary');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'total_pemasukan',
                    'total_pengeluaran',
                    'saldo',
                    'omzet',
                    'laba',
                    'breakdown_harian',
                    'breakdown_bulanan',
                    'breakdown_kategori',
                ],
            ]);
    }

    public function test_staff_can_view_and_create_finance_but_cannot_delete(): void
    {
        $staff = User::factory()->staff()->create();
        $staffToken = $staff->createToken('staff_token')->plainTextToken;

        // Staff creates expense
        $createRes = $this->withHeader('Authorization', "Bearer {$staffToken}")
            ->postJson('/api/finance', [
                'tipe' => 'pengeluaran',
                'nominal' => 75000,
                'kategori' => 'Packaging',
                'catatan' => 'Beli box kue 50 pcs',
                'tanggal' => now()->toDateString(),
            ]);
        $createRes->assertStatus(201);
        $txId = $createRes->json('data.id');

        // Staff tries to delete -> 403 Forbidden
        $deleteStaffRes = $this->withHeader('Authorization', "Bearer {$staffToken}")
            ->deleteJson("/api/finance/{$txId}");
        $deleteStaffRes->assertStatus(403);
    }

    public function test_admin_can_delete_finance_transaction(): void
    {
        $admin = User::where('email', 'admin@erlesbakery.com')->first();
        $adminToken = $admin->createToken('admin_token')->plainTextToken;

        $tx = FinanceTransaction::create([
            'tipe' => 'pengeluaran',
            'nominal' => 50000,
            'kategori' => 'Operasional',
            'catatan' => 'Beli token listrik',
            'tanggal' => now()->toDateString(),
            'user_id' => $admin->id,
        ]);

        $deleteAdminRes = $this->withHeader('Authorization', "Bearer {$adminToken}")
            ->deleteJson("/api/finance/{$tx->id}");
        $deleteAdminRes->assertStatus(200);

        $this->assertDatabaseMissing('finance_transactions', ['id' => $tx->id]);
    }

    public function test_dashboard_endpoint_returns_metrics_and_trends(): void
    {
        $admin = User::where('email', 'admin@erlesbakery.com')->first();
        $token = $admin->createToken('admin_token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'ringkasan' => [
                        'total_pemasukan',
                        'total_pengeluaran',
                        'saldo',
                        'omzet',
                        'laba',
                        'total_orders',
                        'active_orders',
                        'completed_orders',
                        'cancelled_orders',
                        'total_customers',
                        'total_products',
                    ],
                    'pesanan_terbaru',
                    'transaksi_terbaru',
                    'trend_harian',
                    'produk_terlaris',
                ],
            ]);
    }
}
