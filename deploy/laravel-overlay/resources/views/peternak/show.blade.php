@extends('layouts.peternak', ['title' => 'Ternak #'.$ternak->id])

@section('page')
<a href="{{ route('ternak.index') }}" class="text-sm text-brand-600 hover:underline">← Ternak Saya</a>
<div class="flex items-center gap-3 mt-2">
    <span class="text-3xl">{{ $ternak->jenis === 'kambing' ? '🐐' : '🐄' }}</span>
    <div>
        <h1 class="text-2xl font-bold text-brand-800">Ternak #{{ $ternak->id }}</h1>
        <p class="text-sm text-brand-500">{{ ucfirst($ternak->jenis) }}{{ $ternak->ras ? ' · '.$ternak->ras : '' }}{{ $ternak->kelamin ? ' · '.$ternak->kelamin : '' }}{{ $ternak->umur_estimasi_bulan ? ' · '.$ternak->umur_estimasi_bulan.' bln' : '' }}</p>
    </div>
</div>

@if (session('estimasi') && !isset(session('estimasi')['error']))
    @php $e = session('estimasi'); @endphp
    <div class="bg-gradient-to-br from-brand-600 to-brand-800 text-white rounded-2xl p-6 animate-pop">
        <p class="text-brand-100 text-sm">Estimasi bobot terbaru</p>
        <p class="text-5xl font-extrabold my-1">{{ $e['bobot_estimasi_kg'] }} <span class="text-xl">kg</span></p>
        <p class="text-brand-100/90 text-sm">rentang wajar {{ $e['p10'] }}–{{ $e['p90'] }} kg · model {{ $e['model_ver'] }}</p>
    </div>
@endif

<div class="grid md:grid-cols-2 gap-6">
    {{-- Form pengukuran (tanpa bobot) --}}
    <div class="bg-white rounded-2xl border border-brand-100 shadow-sm p-6">
        <h2 class="font-bold text-brand-800 mb-1">Ukur & Estimasi</h2>
        <p class="text-xs text-brand-500 mb-4">Masukkan ukuran tubuh (cm). Sistem menghitung estimasi bobot — Anda tidak perlu menimbang.</p>
        <x-help title="Kenapa hasilnya berupa rentang (p10–p90)?">
            <p>Estimasi tak pernah pasti 100%. Sistem memberi <b>rentang wajar</b>: kemungkinan besar (≈80%) bobot asli ada
            di antara p10 dan p90. Ini lebih jujur daripada satu angka, dan membantu Anda menawar dengan percaya diri.
            Cukup ukur <b>lingkar dada</b> (melingkari dada di belakang kaki depan) — panjang badan & tinggi gumba opsional
            untuk menambah ketelitian.</p>
        </x-help>
        <form method="POST" action="{{ route('pengukuran.store', $ternak) }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1">Lingkar dada (cm) *</label>
                <input name="lingkar_dada_cm" type="number" step="0.1" required
                       class="w-full rounded-xl border-brand-200 focus:ring-brand-500 focus:border-brand-500 bg-brand-50/50 px-4 py-2.5">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium mb-1">Panjang badan</label>
                    <input name="panjang_badan_cm" type="number" step="0.1"
                           class="w-full rounded-xl border-brand-200 focus:ring-brand-500 focus:border-brand-500 bg-brand-50/50 px-4 py-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Tinggi gumba</label>
                    <input name="tinggi_gumba_cm" type="number" step="0.1"
                           class="w-full rounded-xl border-brand-200 focus:ring-brand-500 focus:border-brand-500 bg-brand-50/50 px-4 py-2.5">
                </div>
            </div>
            <button class="w-full py-3 rounded-xl bg-brand-600 text-white font-semibold hover:bg-brand-700 transition">Hitung estimasi</button>
        </form>
    </div>

    {{-- Riwayat --}}
    <div class="bg-white rounded-2xl border border-brand-100 shadow-sm p-6">
        <h2 class="font-bold text-brand-800 mb-4">Riwayat Pengukuran</h2>
        @forelse ($ternak->pengukuran as $p)
            <div class="flex items-center justify-between py-2 border-b border-brand-50 last:border-0">
                <div class="text-sm">
                    <p class="text-brand-700">LD {{ $p->lingkar_dada_cm }}{{ $p->panjang_badan_cm ? ' · PB '.$p->panjang_badan_cm : '' }}{{ $p->tinggi_gumba_cm ? ' · TG '.$p->tinggi_gumba_cm : '' }}</p>
                    <p class="text-xs text-brand-400">{{ $p->tanggal?->format('d/m/Y') }}</p>
                </div>
                <div class="text-right">
                    <p class="font-bold text-brand-700">{{ $p->bobot_estimasi_kg ?? '—' }} kg</p>
                </div>
            </div>
        @empty
            <p class="text-sm text-brand-400">Belum ada pengukuran.</p>
        @endforelse
    </div>
</div>
@endsection
