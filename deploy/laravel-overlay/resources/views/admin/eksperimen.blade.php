@extends('layouts.admin', ['title' => 'Eksperimen #'.$exp->id])
@section('heading', 'Eksperimen #'.$exp->id.' — '.($exp->method_label ?? $exp->method))

@section('page')
<div class="flex flex-wrap items-center gap-3">
    <span class="px-3 py-1 rounded-full bg-brand-100 text-brand-700 text-sm">Fitur: {{ implode(', ', $exp->features ?? []) }}</span>
    <span class="px-3 py-1 rounded-full bg-brand-100 text-brand-700 text-sm">Skenario: {{ $exp->scenario ?? 'B' }}</span>
    <span class="px-3 py-1 rounded-full bg-brand-100 text-brand-700 text-sm">Eval: {{ $exp->eval_mode }}</span>
    @if ($exp->log_target)<span class="px-3 py-1 rounded-full bg-brand-100 text-brand-700 text-sm">target: ln(bobot)</span>@endif
    <span class="px-3 py-1 rounded-full bg-brand-100 text-brand-700 text-sm">n uji = {{ number_format($exp->n_rows) }}</span>
    @if ($exp->is_active)
        <span class="px-3 py-1 rounded-full bg-green-600 text-white text-sm">★ Model aktif</span>
    @else
        <form method="POST" action="{{ route('admin.model.promote', $exp) }}">
            @csrf
            <button class="px-4 py-1.5 rounded-full bg-brand-600 text-white text-sm font-semibold hover:bg-brand-700 transition">🚀 Jadikan model aktif</button>
        </form>
    @endif
    <a href="{{ route('admin.leaderboard') }}" class="text-sm text-brand-600 hover:underline ml-auto">← leaderboard</a>
</div>

<x-help title="Cara membaca halaman ini" :open="true">
    <p>Halaman ini menilai <b>satu model</b> pada data uji yang belum pernah dilihatnya. Tujuh kartu = <b>metrik akurasi</b>;
    dua grafik = <b>diagnosa</b> (di mana model meleset, bukan cuma seberapa); bagian bawah = <b>interpretabilitas</b>
    (kenapa model menebak begitu). Bila model ini terbaik, klik <b>Jadikan model aktif</b> agar dipakai melayani estimasi peternak.</p>
</x-help>

{{-- Metrik --}}
<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
    @foreach ([
        ['MAPE', $exp->mape, '%'], ['MAE', $exp->mae, 'kg'], ['RMSE', $exp->rmse, 'kg'],
        ['R²', $exp->r2, ''], ['Bias', $exp->bias, 'kg'], ['Coverage', $exp->coverage, '%'],
        ['Interval', $exp->interval_kg, 'kg'],
    ] as $m)
        <div class="bg-white rounded-2xl p-4 border border-brand-100 shadow-sm text-center">
            <p class="text-xs text-brand-500">{{ $m[0] }}</p>
            <p class="text-xl font-extrabold text-brand-800">{{ $m[1] !== null ? $m[1] : '—' }}<span class="text-xs font-normal">{{ $m[2] }}</span></p>
        </div>
    @endforeach
</div>

<x-help title="Arti tiap metrik">
    <ul class="list-disc pl-5 space-y-1">
        <li><b>MAPE</b> (%): rata-rata error relatif — metrik utama, paling mudah dijelaskan ke peternak. Makin kecil makin baik.</li>
        <li><b>MAE</b> (kg): rata-rata meleset berapa kg. Konkret.</li>
        <li><b>RMSE</b> (kg): seperti MAE tapi menghukum error besar lebih keras — peka pada kesalahan ekstrem.</li>
        <li><b>R²</b> (0–1): proporsi variasi bobot yang dijelaskan model. Mendekati 1 = sangat baik; negatif = lebih buruk dari menebak rata-rata.</li>
        <li><b>Bias</b> (kg): rata-rata error bertanda. Positif = cenderung over-estimate, negatif = under-estimate. Idealnya ~0.</li>
        <li><b>Coverage</b> (%): berapa % bobot asli jatuh dalam rentang p10–p90. Idealnya ~80% (interval jujur).</li>
        <li><b>Interval</b> (kg): lebar rata-rata p10–p90. Sempit + coverage tepat = model percaya diri & benar.</li>
    </ul>
</x-help>

{{-- Grafik diagnostik --}}
<div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white rounded-2xl border border-brand-100 shadow-sm p-5">
        <h2 class="font-bold text-brand-800 mb-3">Prediksi vs Aktual</h2>
        <canvas id="scatter" height="240"></canvas>
        <p class="text-xs text-brand-500 mt-2">Makin dekat ke garis diagonal = makin akurat.</p>
    </div>
    <div class="bg-white rounded-2xl border border-brand-100 shadow-sm p-5">
        <h2 class="font-bold text-brand-800 mb-3">Sebaran Residual (aktual − prediksi)</h2>
        <canvas id="resid" height="240"></canvas>
        <p class="text-xs text-brand-500 mt-2">Idealnya simetris di sekitar 0.</p>
    </div>
</div>

<x-help title="Cara membaca dua grafik di atas">
    <p><b>Prediksi vs Aktual</b>: tiap titik satu ekor sapi (x=bobot asli, y=tebakan model). Garis putus-putus = tebakan
    sempurna. Titik di atas garis = over-estimate, di bawah = under-estimate. Makin rapat ke garis, makin akurat.</p>
    <p><b>Sebaran Residual</b>: histogram selisih (aktual − prediksi). Idealnya <b>simetris & memuncak di 0</b>. Bila miring
    atau melebar untuk sapi besar → pertanda perlu target log / model lain. Pola "corong" = heteroskedastisitas.</p>
</x-help>

{{-- Interpretabilitas --}}
<div class="bg-white rounded-2xl border border-brand-100 shadow-sm p-5">
    <h2 class="font-bold text-brand-800 mb-3">Interpretabilitas</h2>
    <p class="text-xs text-brand-500 mb-3">Menjawab "kenapa model menebak segini": untuk regresi = <b>koefisien</b> tiap fitur; untuk pohon (RF/XGBoost/hibrida) = <b>feature importance</b> (seberapa sering & berguna fitur dipakai); untuk log-log = <b>eksponen LD</b> (idealnya ~2,5–3, sesuai biologi).</p>
    @if ($exp->importance)
        <div class="flex flex-wrap gap-2">
            @foreach ($exp->importance as $k => $v)
                <span class="px-3 py-1.5 rounded-lg bg-brand-50 border border-brand-100 text-sm text-brand-700"><b>{{ $k }}</b>: {{ $v }}</span>
            @endforeach
        </div>
    @else
        <p class="text-sm text-brand-500">Tidak ada data interpretabilitas.</p>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    const diag = @json($exp->diagnostics ?? ['actual'=>[],'pred'=>[],'residual'=>[]]);
    const green = '#16a34a';

    // scatter prediksi vs aktual + garis ideal
    const pts = diag.actual.map((a,i) => ({x:a, y:diag.pred[i]}));
    const lo = Math.min(...diag.actual, ...diag.pred), hi = Math.max(...diag.actual, ...diag.pred);
    new Chart(document.getElementById('scatter'), {
        data: {
            datasets: [
                {type:'scatter', label:'titik', data:pts, backgroundColor:'rgba(22,163,74,.5)', pointRadius:3},
                {type:'line', label:'ideal', data:[{x:lo,y:lo},{x:hi,y:hi}], borderColor:'#9ca3af', borderDash:[6,4], pointRadius:0}
            ]
        },
        options: {scales:{x:{title:{display:true,text:'aktual (kg)'}}, y:{title:{display:true,text:'prediksi (kg)'}}}, plugins:{legend:{display:false}}}
    });

    // histogram residual
    const r = diag.residual;
    if (r.length) {
        const rmin = Math.min(...r), rmax = Math.max(...r), bins = 15, w = (rmax - rmin)/bins || 1;
        const counts = new Array(bins).fill(0), labels = [];
        r.forEach(v => { let b = Math.min(bins-1, Math.floor((v-rmin)/w)); counts[b]++; });
        for (let i=0;i<bins;i++) labels.push(Math.round(rmin + (i+0.5)*w));
        new Chart(document.getElementById('resid'), {
            type:'bar',
            data:{labels, datasets:[{data:counts, backgroundColor:green}]},
            options:{plugins:{legend:{display:false}}, scales:{x:{title:{display:true,text:'residual (kg)'}}}}
        });
    }
</script>
@endsection
