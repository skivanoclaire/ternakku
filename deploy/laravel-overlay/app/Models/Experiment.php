<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu eksperimen pelatihan model (Modul 1). Diaudit otomatis
 * (siapa melatih/menghapus) lewat trait Auditable.
 */
class Experiment extends Model
{
    use Auditable;

    protected $fillable = [
        'user_id', 'method', 'method_label', 'features', 'eval_mode', 'scenario', 'n_rows',
        'mape', 'mae', 'rmse', 'r2', 'bias', 'coverage', 'interval_kg',
        'model_ver', 'importance', 'diagnostics', 'is_active',
    ];

    protected $casts = [
        'features'    => 'array',
        'importance'  => 'array',
        'diagnostics' => 'array',
        'is_active'   => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
