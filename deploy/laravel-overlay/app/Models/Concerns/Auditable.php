<?php

namespace App\Models\Concerns;

use App\Models\Audit;

/**
 * Tambahkan trait ini ke model yang ingin diaudit (mis. Ternak, Listing, HargaSnapshot):
 *   use Auditable;
 * Setiap create/update/delete otomatis dicatat ke tabel `audits`
 * (siapa, event, nilai lama->baru, IP, URL). Pemenuhan audit trail.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn ($m) => $m->writeAudit('created', null, $m->getAttributes()));
        static::updated(fn ($m) => $m->writeAudit('updated', $m->getOriginal(), $m->getChanges()));
        static::deleted(fn ($m) => $m->writeAudit('deleted', $m->getOriginal(), null));
    }

    public function writeAudit(string $event, ?array $old, ?array $new): void
    {
        $req = request();
        Audit::create([
            'user_id'        => auth()->id(),
            'event'          => $event,
            'auditable_type' => static::class,
            'auditable_id'   => $this->getKey(),
            'old_values'     => $old,
            'new_values'     => $new,
            'ip_address'     => $req?->ip(),
            'user_agent'     => $req?->userAgent(),
            'url'            => $req?->fullUrl(),
        ]);
    }
}
