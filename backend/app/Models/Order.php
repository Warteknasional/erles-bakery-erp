<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'kode_pesanan',
        'customer_name',
        'customer_phone',
        'alamat',
        'catatan',
        'tanggal_ambil',
        'total_price',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:2',
            'tanggal_ambil' => 'date',
        ];
    }

    /**
     * Customer who placed this order.
     */
    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Items in this order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Finance transactions linked to this order.
     */
    public function financeTransactions(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class);
    }

    /**
     * Generate a unique order code like ORD-20261001-0001.
     */
    public static function generateKodePesanan(): string
    {
        $date = now()->format('Ymd');
        $prefix = "ORD-{$date}-";

        $lastOrder = static::where('kode_pesanan', 'like', "{$prefix}%")
            ->orderByDesc('kode_pesanan')
            ->first();

        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->kode_pesanan, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Recalculate total_price from items.
     */
    public function recalculateTotal(): void
    {
        $this->total_price = $this->items()->sum('subtotal');
        $this->saveQuietly();
    }

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_READY = 'ready';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Normalized statuses mapping for backward compatibility.
     */
    public static array $statusAliases = [
        'diproses' => 'processing',
        'selesai' => 'completed',
        'dibatalkan' => 'cancelled',
    ];

    /**
     * Allowed transition rules.
     */
    public static array $allowedTransitions = [
        'pending' => ['confirmed', 'processing', 'cancelled', 'diproses', 'dibatalkan'],
        'confirmed' => ['processing', 'cancelled', 'diproses', 'dibatalkan'],
        'processing' => ['ready', 'completed', 'cancelled', 'selesai', 'dibatalkan'],
        'ready' => ['completed', 'cancelled', 'selesai', 'dibatalkan'],
        'completed' => [],
        'cancelled' => [],
        'diproses' => ['ready', 'completed', 'cancelled', 'selesai', 'dibatalkan'],
        'selesai' => [],
        'dibatalkan' => [],
    ];

    /**
     * Normalize status string.
     */
    public static function normalizeStatus(string $status): string
    {
        $lower = strtolower($status);
        return self::$statusAliases[$lower] ?? $lower;
    }

    /**
     * Check if transition from current status to target status is valid.
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $current = self::normalizeStatus($this->status);
        $target = self::normalizeStatus($newStatus);

        if ($current === $target) {
            return true;
        }

        $allowed = self::$allowedTransitions[$current] ?? [];
        $normalizedAllowed = array_map([self::class, 'normalizeStatus'], $allowed);

        return in_array($target, $normalizedAllowed, true);
    }

    /**
     * Scope: filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
