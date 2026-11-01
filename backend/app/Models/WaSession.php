<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaSession extends Model
{
    protected $fillable = [
        'phone',
        'step',
        'payload',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Determine if the session has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}

