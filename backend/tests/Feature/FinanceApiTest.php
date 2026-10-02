<?php

namespace Tests\Feature;

use App\Models\FinanceTransaction;
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
                    'summary' => ['total_pemasukan', 'total_pengeluaran', 'saldo'],
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
                    'breakdown_kategori',
                ],
            ]);
    }
}
