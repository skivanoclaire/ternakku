<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Menjembatani data latih DB -> service ML. ML melatih dari CSV di volume
 * bersama (/data/out/pengukuran_train.csv) yang ditulis dari baris pengukuran
 * approved yang punya bobot timbang (ground truth) — sumber public/farmer.
 */
class DatasetService
{
    const TRAIN_CSV = '/data/out/pengukuran_train.csv';

    /** Tulis CSV data latih dari DB. Kembalikan jumlah baris. */
    public function exportTrainingCsv(): int
    {
        $rows = DB::table('pengukuran')
            ->whereNotNull('bobot_timbang_kg')
            ->whereNotNull('lingkar_dada_cm')
            ->where('status', 'approved')
            ->get([
                'src_dataset', 'src_animal_id',
                'lingkar_dada_cm', 'panjang_badan_cm', 'tinggi_gumba_cm',
                'bobot_timbang_kg', 'source',
            ]);

        @mkdir(dirname(self::TRAIN_CSV), 0775, true);
        $fp = fopen(self::TRAIN_CSV, 'w');
        fputcsv($fp, ['src_dataset', 'src_animal_id',
            'lingkar_dada_cm', 'panjang_badan_cm', 'tinggi_gumba_cm', 'bobot_timbang_kg', 'source']);
        foreach ($rows as $r) {
            fputcsv($fp, [
                $r->src_dataset ?: 'db', $r->src_animal_id,
                $r->lingkar_dada_cm, $r->panjang_badan_cm, $r->tinggi_gumba_cm, $r->bobot_timbang_kg, $r->source,
            ]);
        }
        fclose($fp);
        return $rows->count();
    }

    /** Ringkasan komposisi data untuk halaman kelola data. */
    public function stats(): array
    {
        $t = DB::table('pengukuran');
        return [
            'total'        => (clone $t)->count(),
            'public'       => (clone $t)->where('source', 'public')->count(),
            'farmer'       => (clone $t)->where('source', 'farmer')->count(),
            'ground_truth' => (clone $t)->whereNotNull('bobot_timbang_kg')->count(),
            'per_dataset'  => (clone $t)->whereNotNull('bobot_timbang_kg')
                ->select('src_dataset', DB::raw('count(*) as n'))
                ->groupBy('src_dataset')->pluck('n', 'src_dataset')->toArray(),
        ];
    }
}
