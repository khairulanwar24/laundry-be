<?php

namespace App\Models;

use App\Domain\Permissions\OutletPermissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserOutlet extends Model
{
    use HasFactory;

    public const ROLE_OWNER = 'owner';

    public const ROLE_KARYAWAN = 'karyawan';

    protected $fillable = [
        'user_id',
        'outlet_id',
        'role',
        'permissions_json',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'permissions_json' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    /**
     * Get effective permissions for this user-outlet relationship.
     */
    public function getEffectivePermissions(): array
    {
        $basePermissions = OutletPermissions::defaultsFor($this->role);

        return OutletPermissions::merge($basePermissions, $this->permissions_json ?? []);
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->getEffectivePermissions();

        return $permissions[$permission] ?? false;
    }
}
