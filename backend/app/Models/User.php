<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is staff / karyawan.
     */
    public function isStaff(): bool
    {
        return in_array($this->role, ['staff', 'karyawan']);
    }

    /**
     * Check if user has specific role(s).
     */
    public function hasRole(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : explode(',', $roles);
        if (in_array($this->role, $roles)) {
            return true;
        }
        if (in_array('staff', $roles) && $this->role === 'karyawan') {
            return true;
        }
        if (in_array('karyawan', $roles) && $this->role === 'staff') {
            return true;
        }
        return false;
    }

    /**
     * Finance transactions recorded by this user.
     */
    public function financeTransactions(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class);
    }
}
