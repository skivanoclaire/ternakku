<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seed dataset publik awal (pengukuran_public.csv hasil prep) ke tabel pengukuran
 * sebagai source=public ber-bobot timbang. Idempotent: lewati bila sudah ada.
 * Membuat data latih awal terlihat & terkelola di DB.
 */
class PublicDataSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('pengukuran')->where('source', 'public')->exists()) {
            return; // sudah di-seed
        }
        $csv = '/data/out/pengukuran_public.csv';
        if (! is_file($csv)) {
            return; // ml belum prep; lewati
        }
        $fh = fopen($csv, 'r');
        $header = fgetcsv($fh);
        $idx = array_flip(array_map('trim', $header));
        $get = fn ($r, $k) => isset($idx[$k]) && $r[$idx[$k]] !== '' ? $r[$idx[$k]] : null;
        $num = fn ($v) => is_numeric($v) ? (float) $v : null;

        $batch = [];
        while (($r = fgetcsv($fh)) !== false) {
            $batch[] = [
                'src_dataset'       => $get($r, 'src_dataset'),
                'src_animal_id'     => $get($r, 'src_animal_id'),
                'lingkar_dada_cm'   => $num($get($r, 'lingkar_dada_cm')),
                'panjang_badan_cm'  => $num($get($r, 'panjang_badan_cm')),
                'tinggi_gumba_cm'   => $num($get($r, 'tinggi_gumba_cm')),
                'bobot_timbang_kg'  => $num($get($r, 'bobot_timbang_kg')),
                'source'            => 'public',
                'status'            => 'approved',
                'created_at'        => now(), 'updated_at' => now(),
            ];
            if (count($batch) >= 500) { DB::table('pengukuran')->insert($batch); $batch = []; }
        }
        if ($batch) { DB::table('pengukuran')->insert($batch); }
        fclose($fh);
    }
}
