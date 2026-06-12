@extends('layouts.admin', ['title' => 'Eksperimen #'.$exp->id])
@section('heading', 'Eksperimen #'.$exp->id.' — '.($exp->method_label ?? $exp->method))

@section('page')
<div class="flex flex-wrap items-center gap-3">
    <span class="px-3 py-1 rounded-full bg-brand-100 text-brand-700 text-sm">Fitur: {{ implode(', ', $exp->features ?? []) }}</span>
    <span class="px-3 py-1 rounded-full bg-brand-100 text-brand-700 text-sm">Eval: {{ $exp->eval_mode }}</span>
    <span class="px-3 py-1 rounded-full bg-brand-100 text-brand-700 text-sm">n = {{ number_format($exp->n_rows) }}</span>
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

{{-- Interpretabilitas --}}
<div class="bg-white rounded-2xl border border-brand-100 shadow-sm p-5">
    <h2 class="font-bold text-brand-800 mb-3">Interpretabilitas</h2>
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
