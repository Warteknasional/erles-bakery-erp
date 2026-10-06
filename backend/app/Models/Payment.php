<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'nominal',
        'metode',
        'tipe',
        'tanggal',
        'catatan',
        'bukti_bayar',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'float',
            'tanggal' => 'date:Y-m-d',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function financeTransaction(): HasOne
    {
        return $this->hasOne(FinanceTransaction::class, 'payment_id');
    }

    public function scopeFilterByMetode($query, ?string $metode)
    {
        if ($metode) {
            $query->where('metode', strtolower($metode));
        }
        return $query;
    }

    public function scopeFilterByTipe($query, ?string $tipe)
    {
        if ($tipe) {
            $query->where('tipe', strtolower($tipe));
        }
        return $query;
    }

    public function scopeDateBetween($query, ?string $start, ?string $end)
    {
        if ($start) {
            $query->whereDate('tanggal', '>=', $start);
        }
        if ($end) {
            $query->whereDate('tanggal', '<=', $end);
        }
        return $query;
    }
}
