<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model ternak — diaudit otomatis (create/update/delete) via trait Auditable,
 * dan akses per-objek diatur TernakPolicy (ABAC: kepemilikan user_id).
 */
class Ternak extends Model
{
    use Auditable;

    protected $table = 'ternak';
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pengukuran(): HasMany
    {
        return $this->hasMany(Pengukuran::class)->latest('id');
    }

    public function pengukuranTerakhir()
    {
        return $this->hasOne(Pengukuran::class)->latestOfMany();
    }
}
