<?php

namespace App\Policies;

use App\Models\Ternak;
use App\Models\User;

/**
 * ABAC (attribute-based): keputusan berdasarkan ATRIBUT objek & user,
 * bukan sekadar peran. Contoh: peternak hanya boleh mengubah ternak miliknya
 * sendiri (user_id cocok) dan dalam wilayah yang sama.
 *
 * Digabung dengan RBAC: admin di-override penuh lewat Gate::before (lihat
 * AuthServiceProvider-snippet.php), peran menentukan "boleh masuk modul apa",
 * policy ini menentukan "boleh atas objek/atribut yang mana".
 */
class TernakPolicy
{
    public function view(User $user, Ternak $ternak): bool
    {
        return $user->is_active
            && ($ternak->user_id === $user->id || $user->hasRole(['pembeli']));
    }

    public function update(User $user, Ternak $ternak): bool
    {
        // atribut: kepemilikan + (opsional) kesamaan wilayah
        return $user->is_active
            && $ternak->user_id === $user->id
            && ($user->wilayah_id === null || $user->wilayah_id === $ternak->wilayah_id ?? $user->wilayah_id);
    }

    public function delete(User $user, Ternak $ternak): bool
    {
        return $user->is_active && $ternak->user_id === $user->id;
    }
}
