<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'kode',
        'nama',
        'kategori',
        'stok',
        'satuan',
        'minimum_stok',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stok' => 'float',
            'minimum_stok' => 'float',
        ];
    }

    /**
     * Accessor for status (Kritis, Menipis, Aman)
     */
    public function getStatusAttribute(): string
    {
        $min = (float) $this->minimum_stok;
        $stok = (float) $this->stok;

        if ($min <= 0) {
            return 'Aman';
        }

        if ($stok <= ($min * 0.5)) {
            return 'Kritis';
        }

        if ($stok <= $min) {
            return 'Menipis';
        }

        return 'Aman';
    }

    /**
     * Append accessors to array/JSON output.
     */
    protected $appends = ['status'];
}

