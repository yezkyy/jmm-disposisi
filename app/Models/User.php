<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'division_id',
        'is_active',
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
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relasi ke peran utama pengguna.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Relasi ke divisi tempat pengguna bertugas.
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Rekening yang dibuat oleh pengguna ini.
     */
    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class, 'created_by');
    }

    /**
     * Periksa apakah pengguna memiliki salah satu role yang ditentukan.
     *
     * @param string|array $roles
     */
    public function hasRole(string|array $roles): bool
    {
        if (!$this->relationLoaded('role')) {
            $this->load('role');
        }

        if (!$this->role) {
            return false;
        }

        $roles = is_array($roles) ? $roles : [$roles];

        return in_array($this->role->name, $roles, true);
    }

    /**
     * Apakah role pengguna adalah Admin/Finance.
     */
    public function isAdminFinance(): bool
    {
        return $this->hasRole(Role::ROLE_ADMIN_FINANCE);
    }

    /**
     * Apakah role pengguna adalah Manager.
     */
    public function isManager(): bool
    {
        return $this->hasRole(Role::ROLE_MANAGER);
    }

    /**
     * Apakah role pengguna adalah Owner.
     */
    public function isOwner(): bool
    {
        return $this->hasRole(Role::ROLE_OWNER);
    }

    /**
     * Apakah role pengguna adalah Pemohon.
     */
    public function isPemohon(): bool
    {
        return $this->hasRole(Role::ROLE_PEMOHON);
    }

    /**
     * Scope pengguna yang berstatus aktif.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
