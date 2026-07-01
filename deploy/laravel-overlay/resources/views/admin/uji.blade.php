@extends('layouts.admin', ['title' => 'Uji Model'])
@section('heading', 'Uji Model — Validasi Data Eksternal')

@section('page')
<x-help title="Untuk apa halaman ini?" :open="true">
    <p>Uji model pada <b>data uji yang Anda unggah sendiri</b> — data sapi ber-bobot asli yang <b>terpisah</b> dari data
    latih (mis. dataset sapi lokal/jurnal). Sistem menjalankan model pada data itu lalu menghitung akurasinya.</p>
    <p>Data uji di sini <b>TIDAK dilatihkan</b> (tidak masuk ke data latih) — murni untuk menilai. Inilah
    <b>validasi eksternal</b>: menjawab "seberapa akurat model pada populasi nyata/baru?".</p>
</x-help>

<div class="bg-white rounded-2xl border border-brand-100 shadow-sm p-6 max-w-2xl">
    <h2 class="font-bold text-brand-800 mb-1">Data Uji</h2>
    <p class="text-xs text-brand-500 mb-4">Isi <b>beberapa baris manual</b> (cepat) <b>atau</b> unggah <b>CSV</b> (banyak baris). Butuh ukuran tubuh + <b>bobot asli</b> sebagai pembanding.</p>
    <form method="POST" action="{{ route('admin.uji.run') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">Model yang diuji</label>
            <select name="model_ver" class="w-full rounded-xl border-brand-200 bg-brand-50/50 px-4 py-2.5">
                <option value="active">Model aktif (yang melayani peternak)</option>
                @foreach ($models as $m)
                    <option value="{{ $m->model_ver }}">{{ $m->model_ver }} — {{ $m->method_label ?? $m->method }} (MAPE {{ $m->mape }})</option>
                @endforeach
            </select>
        </div>
        {{-- Cara 1: isi manual (tidak wajib CSV) --}}
        <div>
            <label class="block text-sm font-medium mb-1">Isi data uji (cara cepat)</label>
            <p class="text-xs text-brand-500 mb-1">Satu baris = <code>LD, PB, TG, bobot_asli</code>. PB &amp; TG boleh dikosongkan (mis. <code>195,,,560</code>).</p>
            <textarea name="manual" rows="4" placeholder="140.8, 113.27, 104.73, 205.9"
                      class="w-full rounded-xl border-brand-200 bg-brand-50/50 px-4 py-2.5 font-mono text-sm">{{ old('manual') }}</textarea>
        </div>

        <div class="flex items-center gap-3 text-xs text-brand-400">
            <span class="flex-1 h-px bg-brand-100"></span>— atau unggah CSV —<span class="flex-1 h-px bg-brand-100"></span>
        </div>

        {{-- Cara 2: unggah CSV (opsional) --}}
        <div x-data="{ buka:false }">
            <button type="button" @click="buka=!buka" class="text-sm font-medium text-brand-700 hover:underline">
                <span x-text="buka ? '− Sembunyikan opsi CSV' : '+ Unggah file CSV (banyak baris)'"></span>
            </button>
            <div x-show="buka" x-cloak class="mt-3 space-y-3">
                <input type="file" name="csv" accept=".csv,.txt" class="w-full text-sm rounded-xl border border-brand-200 bg-brand-50/50 px-3 py-2.5">
                <p class="text-xs text-brand-500">Sebutkan nama kolom sesuai header CSV-mu:</p>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block text-xs font-medium mb-1">Kolom lingkar dada</label>
                        <input name="col_ld" value="{{ old('col_ld','lingkar_dada_cm') }}" class="w-full rounded-xl border-brand-200 bg-brand-50/50 px-4 py-2"></div>
                    <div><label class="block text-xs font-medium mb-1">Kolom bobot asli</label>
                        <input name="col_bobot" value="{{ old('col_bobot','bobot_timbang_kg') }}" class="w-full rounded-xl border-brand-200 bg-brand-50/50 px-4 py-2"></div>
                    <div><label class="block text-xs font-medium mb-1">Kolom panjang badan</label>
                        <input name="col_pb" value="{{ old('col_pb','panjang_badan_cm') }}" class="w-full rounded-xl border-brand-200 bg-brand-50/50 px-4 py-2"></div>
                    <div><label class="block text-xs font-medium mb-1">Kolom tinggi gumba</label>
                        <input name="col_gumba" value="{{ old('col_gumba','tinggi_gumba_cm') }}" class="w-full rounded-xl border-brand-200 bg-brand-50/50 px-4 py-2"></div>
                </div>
            </div>
        </div>

        <button class="w-full py-3 rounded-xl bg-brand-600 text-white font-semibold hover:bg-brand-700 transition">Uji model</button>
    </form>
</div>

@isset($hasil)
@if($hasil)
    @php $m = $hasil['metrics'] ?? []; @endphp
    <div class="flex items-center gap-3 flex-wrap">
        <h2 class="text-xl font-bold text-brand-800">Hasil Uji</h2>
        <span class="text-xs px-2.5 py-1 rounded-full bg-brand-100 text-brand-700">model: {{ $hasil['model_label'] ? $hasil['model_label'].' ('.$hasil['model_ver'].')' : $hasil['model_ver'] }}{{ ($hasil['model_aktif'] ?? false) ? ' · aktif' : '' }}</span>
        <span class="text-xs px-2.5 py-1 rounded-full bg-brand-100 text-brand-700">{{ $hasil['n'] }} baris diuji</span>
        @if(($hasil['dilewati'] ?? 0) > 0)<span class="text-xs px-2.5 py-1 rounded-full bg-amber-100 text-amber-700">{{ $hasil['dilewati'] }} dilewati</span>@endif
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
        @foreach ([
            ['MAPE', $m['mape'] ?? null, '%'], ['MAE', $m['mae'] ?? null, 'kg'], ['RMSE', $m['rmse'] ?? null, 'kg'],
            ['R²', $m['r2'] ?? null, ''], ['Bias', $m['bias'] ?? null, 'kg'], ['Coverage', $m['coverage'] ?? null, '%'],
            ['Interval', $m['interval_kg'] ?? null, 'kg'],
        ] as $c)
            <div class="bg-white rounded-2xl p-4 border border-brand-100 shadow-sm text-center">
                <p class="text-xs text-brand-500">{{ $c[0] }}</p>
                <p class="text-xl font-extrabold text-brand-800">{{ $c[1] !== null ? $c[1] : '—' }}<span class="text-xs font-normal">{{ $c[2] }}</span></p>
            </div>
        @endforeach
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-brand-100 shadow-sm p-5">
            <h3 class="font-bold text-brand-800 mb-3">Prediksi vs Aktual (data uji)</h3>
            <canvas id="ujiScatter" height="240"></canvas>
            <p class="text-xs text-brand-500 mt-2">Makin dekat garis diagonal = makin akurat.</p>
        </div>
        <div class="bg-white rounded-2xl border border-brand-100 shadow-sm overflow-auto" style="max-height:320px">
            <table class="w-full text-sm">
                <thead class="text-left text-brand-500 bg-brand-50/60 sticky top-0"><tr>
                    <th class="px-4 py-2">LD</th><th class="px-4 py-2">PB</th><th class="px-4 py-2">TG</th><th class="px-4 py-2">Aktual</th><th class="px-4 py-2">Prediksi</th><th class="px-4 py-2">Error%</th>
                </tr></thead>
                <tbody class="divide-y divide-brand-50">
                    @foreach (array_slice($hasil['detail'] ?? [], 0, 200) as $d)
                        <tr class="hover:bg-brand-50/50">
                            <td class="px-4 py-2">{{ $d['lingkar_dada_cm'] }}</td>
                            <td class="px-4 py-2 text-brand-500">{{ $d['panjang_badan_cm'] ?? '—' }}</td>
                            <td class="px-4 py-2 text-brand-500">{{ $d['tinggi_gumba_cm'] ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $d['aktual'] }}</td>
                            <td class="px-4 py-2 font-medium">{{ $d['prediksi'] }}</td>
                            <td class="px-4 py-2 {{ abs($d['error_pct'] ?? 0) > 20 ? 'text-red-600' : 'text-green-600' }}">{{ $d['error_pct'] }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        const diag = @json($hasil['diagnostics'] ?? ['actual'=>[], 'pred'=>[]]);
        const pts = diag.actual.map((a,i)=>({x:a, y:diag.pred[i]}));
        const lo = Math.min(...diag.actual, ...diag.pred), hi = Math.max(...diag.actual, ...diag.pred);
        new Chart(document.getElementById('ujiScatter'), {
            data: { datasets: [
                {type:'scatter', data:pts, backgroundColor:'rgba(22,163,74,.5)', pointRadius:3},
                {type:'line', data:[{x:lo,y:lo},{x:hi,y:hi}], borderColor:'#9ca3af', borderDash:[6,4], pointRadius:0}
            ]},
            options: {plugins:{legend:{display:false}}, scales:{x:{title:{display:true,text:'aktual (kg)'}}, y:{title:{display:true,text:'prediksi (kg)'}}}}
        });
    </script>
@endif
@endisset
@endsection
