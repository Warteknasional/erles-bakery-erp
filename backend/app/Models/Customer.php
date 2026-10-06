<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'notes',
        'total_orders',
        'total_spent',
    ];

    protected function casts(): array
    {
        return [
            'total_orders' => 'integer',
            'total_spent' => 'decimal:2',
        ];
    }

    /**
     * Orders made by this customer.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Find existing customer by phone or create a new customer record.
     */
    public static function findOrCreateByPhone(string $phone, string $name, ?string $address = null): self
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        $customer = self::where('phone', $phone)
            ->orWhere('phone', $cleanPhone)
            ->first();

        if ($customer) {
            // Update name or address if customer had empty details
            $updates = [];
            if (empty($customer->name) || $customer->name === 'Pelanggan') {
                $updates['name'] = $name;
            }
            if ($address && empty($customer->address)) {
                $updates['address'] = $address;
            }
            if (!empty($updates)) {
                $customer->update($updates);
            }
            return $customer;
        }

        return self::create([
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'total_orders' => 0,
            'total_spent' => 0,
        ]);
    }

    /**
     * Recalculate total_orders and total_spent.
     */
    public function updateOrderStats(): void
    {
        $this->total_orders = $this->orders()->count();
        $this->total_spent = (float) $this->orders()->sum('total_price');
        $this->saveQuietly();
    }
}
