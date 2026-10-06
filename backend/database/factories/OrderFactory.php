<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'kode_pesanan' => Order::generateKodePesanan(),
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->phoneNumber(),
            'alamat' => fake()->address(),
            'catatan' => null,
            'tanggal_ambil' => now()->addDays(1)->toDateString(),
            'total_price' => 50000,
            'status' => 'pending',
        ];
    }
}
