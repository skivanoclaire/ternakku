<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pengukuran ukuran tubuh ternak. Dari peternak: TANPA bobot_timbang
 * (bobot justru yang diprediksi). bobot_timbang_kg hanya terisi pada data
 * latih nyata yang diimpor researcher (source=public).
 */
class Pengukuran extends Model
{
    use Auditable;

    protected $table = 'pengukuran';
    protected $guarded = [];

    protected $casts = [
        'tanggal'           => 'date',
        'lingkar_dada_cm'   => 'float',
        'panjang_badan_cm'  => 'float',
        'tinggi_gumba_cm'   => 'float',
        'bobot_timbang_kg'  => 'float',
        'bobot_estimasi_kg' => 'float',
    ];

    public function ternak(): BelongsTo
    {
        return $this->belongsTo(Ternak::class);
    }
}
