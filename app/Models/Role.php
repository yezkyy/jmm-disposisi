<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    public const ROLE_PEMOHON = 'pemohon';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_ADMIN_FINANCE = 'admin_finance';
    public const ROLE_OWNER = 'owner';

    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    /**
     * Relasi ke seluruh pengguna yang memiliki peran ini.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

