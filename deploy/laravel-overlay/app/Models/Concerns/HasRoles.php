<?php

namespace App\Models\Concerns;

use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Tambahkan trait ini ke model User: `use HasRoles;` (RBAC).
 * Menyediakan relasi roles() + helper pengecekan peran & permission.
 */
trait HasRoles
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole(string|array $names): bool
    {
        $names = (array) $names;
        return $this->roles()->whereIn('name', $names)->exists();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /** RBAC granular: cek apakah salah satu peran user punya permission ini. */
    public function hasPermission(string $permission): bool
    {
        return $this->roles()
            ->whereHas('permissions', fn ($q) => $q->where('name', $permission))
            ->exists();
    }
}
