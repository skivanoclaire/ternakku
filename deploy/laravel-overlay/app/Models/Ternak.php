<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * Model ternak — diaudit otomatis (create/update/delete) via trait Auditable,
 * dan keputusan akses per-objek diatur oleh TernakPolicy (ABAC).
 */
class Ternak extends Model
{
    use Auditable;

    protected $table = 'ternak';
    protected $guarded = [];
}
