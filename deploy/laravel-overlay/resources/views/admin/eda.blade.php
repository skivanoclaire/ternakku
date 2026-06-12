@extends('layouts.admin', ['title' => 'Eksplorasi Data'])
@section('heading', 'Eksplorasi & Kualitas Data')

@section('page')
@if (isset($eda['error']))
    <div class="rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $eda['error'] }}</div>
@else
    <div class="flex flex-wrap gap-3">
        <div class="bg-white rounded-2xl p-5 border border-brand-100 shadow-sm">
            <p class="text-xs text-brand-500">Total baris</p>
            <p class="text-3xl font-extrabold text-brand-800">{{ number_format($eda['n_baris'] ?? 0) }}</p>
        </div>
        @foreach (($eda['komposisi'] ?? []) as $ds => $n)
            <div class="bg-white rounded-2xl p-5 border border-brand-100 shadow-sm">
                <p class="text-xs text-brand-500">dataset: {{ $ds }}</p>
                <p class="text-3xl font-extrabold text-brand-800">{{ number_format($n) }}</p>
            </div>
        @endforeach
    </div>

    {{-- Statistik deskriptif --}}
    <div class="bg-white rounded-2xl border border-brand-100 shadow-sm overflow-x-auto">
        <div class="px-6 py-4 border-b border-brand-100"><h2 class="font-bold text-brand-800">Statistik Deskriptif</h2></div>
        <table class="w-full text-sm">
            <thead class="text-left text-brand-500 bg-brand-50/60">
                <tr><th class="px-4 py-3">Kolom</th><th class="px-4 py-3">n</th><th class="px-4 py-3">Kosong</th><th class="px-4 py-3">Min</th><th class="px-4 py-3">Maks</th><th class="px-4 py-3">Mean</th><th class="px-4 py-3">Std</th></tr>
            </thead>
            <tbody class="divide-y divide-brand-50">
                @foreach (($eda['stats'] ?? []) as $col => $s)
                    <tr class="hover:bg-brand-50/50">
                        <td class="px-4 py-3 font-medium text-brand-700">{{ $col }}</td>
                        <td class="px-4 py-3">{{ $s['n'] }}</td>
                        <td class="px-4 py-3 {{ $s['kosong']>0 ? 'text-amber-600 font-semibold' : 'text-brand-400' }}">{{ $s['kosong'] }}</td>
                        <td class="px-4 py-3">{{ $s['min'] }}</td>
                        <td class="px-4 py-3">{{ $s['max'] }}</td>
                        <td class="px-4 py-3">{{ $s['mean'] }}</td>
                        <td class="px-4 py-3">{{ $s['std'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        {{-- Korelasi --}}
        <div class="bg-white rounded-2xl border border-brand-100 shadow-sm p-5">
            <h2 class="font-bold text-brand-800 mb-3">Korelasi Lingkar Dada → Bobot</h2>
            <div class="space-y-2">
                @foreach (($eda['korelasi'] ?? []) as $grp => $r)
                    <div>
                        <div class="flex justify-between text-sm mb-1"><span class="text-brand-700">{{ $grp }}</span><span class="font-semibold">{{ $r }}</span></div>
                        <div class="h-2 rounded-full bg-brand-100"><div class="h-2 rounded-full bg-brand-600" style="width: {{ max(0,min(100, $r*100)) }}%"></div></div>
                    </div>
                @endforeach
            </div>
            <p class="text-xs text-brand-500 mt-3">Korelasi tinggi = lingkar dada penanda bobot yang kuat untuk grup itu.</p>
        </div>

        {{-- Scatter --}}
        <div class="bg-white rounded-2xl border border-brand-100 shadow-sm p-5">
            <h2 class="font-bold text-brand-800 mb-3">Lingkar Dada vs Bobot</h2>
            <canvas id="scatter" height="240"></canvas>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        const scatter = @json($eda['scatter'] ?? []);
        const groups = [...new Set(scatter.map(p => p.ds))];
        const palette = ['#16a34a','#15803d','#4ade80','#166534','#86efac'];
        const ds = groups.map((g,i) => ({
            label:g,
            data: scatter.filter(p=>p.ds===g).map(p=>({x:p.ld,y:p.bobot})),
            backgroundColor: palette[i % palette.length], pointRadius:3
        }));
        new Chart(document.getElementById('scatter'), {
            type:'scatter',
            data:{datasets:ds},
            options:{scales:{x:{title:{display:true,text:'lingkar dada (cm)'}}, y:{title:{display:true,text:'bobot (kg)'}}}}
        });
    </script>
@endif
@endsection
