<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'nominal' => fake()->randomElement([50000, 100000, 150000, 200000]),
            'metode' => fake()->randomElement(['cash', 'transfer', 'qris']),
            'tipe' => fake()->randomElement(['dp', 'lunas', 'cicilan']),
            'tanggal' => now()->toDateString(),
            'catatan' => fake()->sentence(),
            'bukti_bayar' => null,
        ];
    }
}
