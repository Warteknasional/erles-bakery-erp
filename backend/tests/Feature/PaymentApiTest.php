<?php

namespace Tests\Feature;

use App\Models\FinanceTransaction;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_unauthenticated_cannot_access_payment_endpoints(): void
    {
        $this->getJson('/api/payments')->assertStatus(401);
        $this->postJson('/api/payments', [])->assertStatus(401);
        $this->getJson('/api/orders/1/payments')->assertStatus(401);
    }

    public function test_staff_and_admin_can_record_dp_payment(): void
    {
        $staff = User::factory()->staff()->create();
        $token = $staff->createToken('staff_token')->plainTextToken;

        $order = Order::factory()->create([
            'total_price' => 100000,
            'payment_status' => 'unpaid',
            'paid_amount' => 0,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/orders/{$order->id}/payments", [
                'nominal' => 40000,
                'metode' => 'transfer',
                'tipe' => 'dp',
                'tanggal' => now()->toDateString(),
                'catatan' => 'Uang muka transfer BCA',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nominal', 40000)
            ->assertJsonPath('data.metode', 'transfer')
            ->assertJsonPath('data.tipe', 'dp');

        $order->refresh();
        $this->assertEquals(40000, (float) $order->paid_amount);
        $this->assertEquals('partial', $order->payment_status);

        // Check auto-created finance transaction
        $paymentId = $response->json('data.id');
        $this->assertDatabaseHas('finance_transactions', [
            'payment_id' => $paymentId,
            'order_id' => $order->id,
            'tipe' => 'pemasukan',
            'nominal' => 40000,
            'kategori' => 'Penjualan',
        ]);
    }

    public function test_full_payment_updates_order_status_to_paid(): void
    {
        $admin = User::where('email', 'admin@erlesbakery.com')->first();
        $token = $admin->createToken('admin_token')->plainTextToken;

        $order = Order::factory()->create([
            'total_price' => 150000,
            'payment_status' => 'unpaid',
            'paid_amount' => 0,
        ]);

        // Pay full amount directly via POST /api/payments
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/payments', [
                'order_id' => $order->id,
                'nominal' => 150000,
                'metode' => 'qris',
                'tipe' => 'lunas',
                'tanggal' => now()->toDateString(),
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $order->refresh();
        $this->assertEquals(150000, (float) $order->paid_amount);
        $this->assertEquals('paid', $order->payment_status);
    }

    public function test_payment_cannot_exceed_remaining_order_total(): void
    {
        $admin = User::where('email', 'admin@erlesbakery.com')->first();
        $token = $admin->createToken('admin_token')->plainTextToken;

        $order = Order::factory()->create([
            'total_price' => 100000,
            'payment_status' => 'unpaid',
            'paid_amount' => 0,
        ]);

        // DP of 60,000
        Payment::create([
            'order_id' => $order->id,
            'nominal' => 60000,
            'metode' => 'cash',
            'tipe' => 'dp',
            'tanggal' => now()->toDateString(),
        ]);
        $order->recalculatePaymentStatus();

        // Attempt to pay 50,000 (remaining is 40,000)
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/orders/{$order->id}/payments", [
                'nominal' => 50000,
                'metode' => 'cash',
                'tipe' => 'lunas',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
        $this->assertStringContainsString('melebihi sisa tagihan', $response->json('message'));

        $order->refresh();
        $this->assertEquals(60000, (float) $order->paid_amount);
    }

    public function test_cannot_pay_for_cancelled_order(): void
    {
        $admin = User::where('email', 'admin@erlesbakery.com')->first();
        $token = $admin->createToken('admin_token')->plainTextToken;

        $order = Order::factory()->create([
            'total_price' => 100000,
            'status' => 'cancelled',
            'payment_status' => 'unpaid',
            'paid_amount' => 0,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/orders/{$order->id}/payments", [
                'nominal' => 50000,
                'metode' => 'cash',
                'tipe' => 'dp',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_admin_can_filter_and_view_payments(): void
    {
        $admin = User::where('email', 'admin@erlesbakery.com')->first();
        $token = $admin->createToken('admin_token')->plainTextToken;

        $order = Order::factory()->create(['total_price' => 200000]);

        Payment::create([
            'order_id' => $order->id,
            'user_id' => $admin->id,
            'nominal' => 80000,
            'metode' => 'transfer',
            'tipe' => 'dp',
            'tanggal' => now()->toDateString(),
        ]);
        $order->recalculatePaymentStatus();

        // Test list all payments
        $listRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/payments');
        $listRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'meta' => ['total', 'total_nominal']]);

        // Test get order payments
        $orderRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/orders/{$order->id}/payments");
        $orderRes->assertStatus(200)
            ->assertJsonPath('meta.payment_status', 'partial')
            ->assertJsonPath('meta.paid_amount', 80000)
            ->assertJsonPath('meta.sisa_pembayaran', 120000);
    }

    public function test_delete_payment_recalculates_order_and_removes_finance_record(): void
    {
        $admin = User::where('email', 'admin@erlesbakery.com')->first();
        $token = $admin->createToken('admin_token')->plainTextToken;

        $order = Order::factory()->create(['total_price' => 100000]);

        // Create payment
        $payment = Payment::create([
            'order_id' => $order->id,
            'user_id' => $admin->id,
            'nominal' => 100000,
            'metode' => 'cash',
            'tipe' => 'lunas',
            'tanggal' => now()->toDateString(),
        ]);
        $order->recalculatePaymentStatus();

        $finance = FinanceTransaction::create([
            'tipe' => 'pemasukan',
            'nominal' => 100000,
            'kategori' => 'Penjualan',
            'catatan' => 'Pembayaran lunas pesanan',
            'tanggal' => now()->toDateString(),
            'order_id' => $order->id,
            'payment_id' => $payment->id,
        ]);

        $this->assertEquals('paid', $order->fresh()->payment_status);

        // Delete payment
        $delRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/payments/{$payment->id}");
        $delRes->assertStatus(200);

        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
        $this->assertDatabaseMissing('finance_transactions', ['id' => $finance->id]);

        $order->refresh();
        $this->assertEquals(0, (float) $order->paid_amount);
        $this->assertEquals('unpaid', $order->payment_status);
    }
}
